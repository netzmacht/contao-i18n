<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\PageModel;
use Contao\Search;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

use function array_keys;
use function defined;
use function in_array;
use function preg_match;
use function preg_quote;
use function strncmp;

class AddToSearchIndexListener
{
    private ContaoFramework $framework;

    private string $fragmentPath;

    /**
     * @param ContaoFramework $framework    Contao framework.
     * @param string          $fragmentPath Fragment path.
     */
    public function __construct(ContaoFramework $framework, string $fragmentPath = '_fragment')
    {
        $this->framework    = $framework;
        $this->fragmentPath = $fragmentPath;
    }

    /**
     * Checks if the request can be indexed and forwards it accordingly.
     *
     * @param ResponseEvent $event The subscribed event.
     */
    public function onKernelTerminate(ResponseEvent $event): void
    {
        if (! $this->framework->isInitialized()) {
            return;
        }

        $request = $event->getRequest();

        // Only index GET requests (see #1194)
        if (! $request->isMethod(Request::METHOD_GET)) {
            return;
        }

        // Do not index fragments
        if (preg_match('~(?:^|/)' . preg_quote($this->fragmentPath, '~') . '/~', $request->getPathInfo())) {
            return;
        }

        $this->indexPageIfApplicable($event->getResponse());
    }

    /**
     * Index a page if applicable
     *
     * @param Response $response The http response.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function indexPageIfApplicable(Response $response): void
    {
        $page = $GLOBALS['objPage'];

        if ($page === null) {
            return;
        }

        // Index page if searching is allowed and there is no back end user
        if (! $this->isIndexingAllowed($page)) {
            return;
        }

        $environment = $this->framework->getAdapter(Environment::class);
        $data        = [
            'url'       => $environment->get('base') . $environment->get('relativeRequest'),
            'content'   => $response->getContent(),
            'title'     => $page->pageTitle ?: $page->title,
            'protected' => ($page->protected ? '1' : ''),
            'groups'    => $page->groups,
            'pid'       => $page->id,
            'language'  => $page->language,
        ];

        $search = $this->framework->getAdapter(Search::class);
        $search->indexPage($data);
    }

    /**
     * Check if indexing is allowed.
     *
     * @param PageModel $page The page model.
     */
    private function isIndexingAllowed($page): bool
    {
        $config = $this->framework->getAdapter(Config::class);

        if (! $config->get('enableSearch')) {
            return false;
        }

        if ($page->type !== 'i18n_regular' || (defined('BE_USER_LOGGED_IN') && BE_USER_LOGGED_IN) || $page->noSearch) {
            return false;
        }

        // Index protected pages if enabled
        if (! $config->get('indexProtected') && defined('FE_USER_LOGGED_IN') && FE_USER_LOGGED_IN && $page->protected) {
            return false;
        }

        return ! $this->hasNoIndexKeys();
    }

    /**
     * Check if query has some no index keys.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function hasNoIndexKeys(): bool
    {
        // Do not index the page if certain parameters are set
        foreach (array_keys($_GET) as $key) {
            if (in_array($key, $GLOBALS['TL_NOINDEX_KEYS']) || strncmp((string) $key, 'page_', 5) === 0) {
                return true;
            }
        }

        return false;
    }
}

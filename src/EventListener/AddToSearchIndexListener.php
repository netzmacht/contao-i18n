<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @package    contao-18n
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later https://github.com/netzmacht/contao-i18n/blob/master/LICENSE
 * @filesource
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface as ContaoFramework;
use Contao\Environment;
use Contao\PageModel;
use Contao\Search;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Class AddToSearchIndexListener
 *
 * @package Netzmacht\Contao\I18n\EventListener
 */
class AddToSearchIndexListener
{
    /**
     * Contao framework.
     *
     * @var ContaoFramework
     */
    private $framework;

    /**
     * Fragment path.
     *
     * @var string
     */
    private $fragmentPath;

    /**
     * Construct.
     *
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
     * @param PostResponseEvent $event The subscribed event.
     *
     * @return void
     */
    public function onKernelTerminate(PostResponseEvent $event): void
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        $request = $event->getRequest();

        // Only index GET requests (see #1194)
        if (!$request->isMethod(Request::METHOD_GET)) {
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
     * @return void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function indexPageIfApplicable(Response $response): void
    {
        $page = $GLOBALS['objPage'];

        if ($page === null) {
            return;
        }

        /** @var Environment $environment */
        $environment = $this->framework->getAdapter(Environment::class);

        // Index page if searching is allowed and there is no back end user
        if ($this->isIndexingAllowed($page)) {
            $data = [
                'url'       => $environment->get('base') . $environment->get('relativeRequest'),
                'content'   => $response->getContent(),
                'title'     => $page->pageTitle ?: $page->title,
                'protected' => ($page->protected ? '1' : ''),
                'groups'    => $page->groups,
                'pid'       => $page->id,
                'language'  => $page->language,
            ];

            /** @var Adapter|Search $search */
            $search = $this->framework->getAdapter(Search::class);
            $search->indexPage($data);
        }
    }

    /**
     * Check if indexing is allowed.
     *
     * @param PageModel $page The page model.
     *
     * @return bool
     */
    private function isIndexingAllowed($page): bool
    {
        /** @var Adapter|Config $config */

        $config = $this->framework->getAdapter(Config::class);

        if (!$config->get('enableSearch')) {
            return false;
        }

        if ($page->type !== 'i18n_regular' || BE_USER_LOGGED_IN || $page->noSearch) {
            return false;
        }

        // Index protected pages if enabled
        if (!$config->get('indexProtected') && FE_USER_LOGGED_IN && $page->protected) {
            return false;
        }

        return !$this->hasNoIndexKeys();
    }

    /**
     * Check if query has some no index keys.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function hasNoIndexKeys(): bool
    {
        // Do not index the page if certain parameters are set
        foreach (array_keys($_GET) as $key) {
            if (\in_array($key, $GLOBALS['TL_NOINDEX_KEYS']) || strncmp($key, 'page_', 5) === 0) {
                return true;
            }
        }

        return false;
    }
}

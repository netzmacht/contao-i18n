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

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Netzmacht\Contao\Toolkit\Dca\Manager;

/**
 * Class PageDcaListener
 */
final class PageDcaListener extends AbstractListener
{
    /**
     * Data container name.
     *
     * @var string
     */
    protected static $name = 'tl_page';

    /**
     * Repository manager.
     *
     * @var RepositoryManager
     */
    private $repositoryManager;

    /**
     * PageDcaListener constructor.
     *
     * @param Manager           $dcaManager        Data container definition manager.
     * @param RepositoryManager $repositoryManager Repository manager.
     */
    public function __construct(Manager $dcaManager, RepositoryManager $repositoryManager)
    {
        parent::__construct($dcaManager);

        $this->repositoryManager = $repositoryManager;
    }

    /**
     * Initialize the palette.
     *
     * @param DataContainer $dataContainer Data container driver.
     *
     * @return void
     */
    public function initializePalette($dataContainer): void
    {
        $definition = $this->getDefinition();
        $definition->set(['palettes', 'i18n_regular'], $definition->get(['palettes', 'regular']));

        if (Input::get('act') !== 'edit') {
            return;
        }

        $repository = $this->repositoryManager->getRepository(PageModel::class);
        $page       = $repository->find((int) $dataContainer->id);

        if (!$page || $page->type === 'root' || $page->type === 'i18n_regular' || $page->languageMain > 0) {
            return;
        }

        PaletteManipulator::create()
            ->addField('i18n_disable', 'expert_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalette('regular', static::getName());
    }
}

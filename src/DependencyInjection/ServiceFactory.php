<?php

/**
 * Contao I18n provides some i18n structures for easily l10n websites.
 *
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2015-2018 netzmacht David Molineus
 * @license    LGPL-3.0-or-later
 * @filesource
 *
 */

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\DependencyInjection;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface as ContaoFramework;
use Contao\Database;
use Contao\Model\Registry;

/**
 * Class ServiceFactory
 */
class ServiceFactory
{
    /**
     * The contao framework.
     *
     * @var ContaoFramework
     */
    private $framework;

    /**
     * ServiceFactory constructor.
     *
     * @param ContaoFramework $framework
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Create the model registry.
     *
     * @return Registry
     */
    public function createModelRegistry(): Registry
    {
        $this->framework->initialize();

        return $this->framework->createInstance(Registry::class);
    }

    /**
     * Create the database connection.
     *
     * @return Database
     */
    public function createDatabaseConnection(): Database
    {
        $this->framework->initialize();

        return $this->framework->createInstance(Database::getInstance());
    }
}

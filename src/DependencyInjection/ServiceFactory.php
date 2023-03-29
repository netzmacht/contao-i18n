<?php

declare(strict_types=1);

namespace Netzmacht\Contao\I18n\DependencyInjection;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\Model\Registry;

final class ServiceFactory
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * Create the model registry.
     */
    public function createModelRegistry(): Registry
    {
        $this->framework->initialize();

        return $this->framework->createInstance(Registry::class);
    }

    /**
     * Create the database connection.
     */
    public function createDatabaseConnection(): Database
    {
        $this->framework->initialize();

        return $this->framework->createInstance(Database::class);
    }
}

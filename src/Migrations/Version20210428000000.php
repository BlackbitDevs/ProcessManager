<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace Elements\Bundle\ProcessManagerBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Elements\Bundle\ProcessManagerBundle\ElementsProcessManagerBundle;
use Pimcore\Migrations\BundleAwareMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20210428000000 extends BundleAwareMigration
{
    protected function getBundleName(): string
    {
        return ElementsProcessManagerBundle::BUNDLE_NAME;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}

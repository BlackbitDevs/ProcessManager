<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace Elements\Bundle\ProcessManagerBundle\Model\CallbackSetting\Listing;

use Elements\Bundle\ProcessManagerBundle\ElementsProcessManagerBundle;
use Elements\Bundle\ProcessManagerBundle\Model\CallbackSetting;
use Pimcore\Model;

class Dao extends Model\Listing\Dao\AbstractDao
{
    protected function getTableName(): string
    {
        return ElementsProcessManagerBundle::TABLE_NAME_CALLBACK_SETTING;
    }

    /**
     * @return array<mixed>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function load(): array
    {
        $sql = 'SELECT id FROM '.$this->getTableName().$this->getCondition().$this->getOrder().$this->getOffsetLimit();
        $ids = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables());

        $items = [];
        foreach ($ids as $id) {
            $items[] = CallbackSetting::getById($id);
        }

        return $items;
    }

    public function getTotalCount(): int
    {
        return (int)$this->db->fetchOne(
            'SELECT COUNT(*) as amount FROM '.$this->getTableName().' '.$this->getCondition(),
            $this->model->getConditionVariables()
        );
    }
}

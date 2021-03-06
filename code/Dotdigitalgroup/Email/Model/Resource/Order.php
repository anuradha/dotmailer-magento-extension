<?php

class Dotdigitalgroup_Email_Model_Resource_Order
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Constructor.
     */
    protected function _construct()
    {
        $this->_init('ddg_automation/order', 'email_order_id');
    }

    /**
     * Get sales_flat_order table description.
     *
     * @return array
     */
    public function getOrderTableDescription()
    {
        return $this->getReadConnection()->describeTable(
            $this->getTable('sales/order')
        );
    }

    /**
     * Reset the email order for re-import.
     *
     * @param null $from
     * @param null $to
     * @return int
     */
    public function resetOrders($from = null, $to = null)
    {
        try {
            $conn = $this->_getWriteAdapter();
            if ($from && $to) {
                $where = array(
                    'created_at >= ?' => $from . ' 00:00:00',
                    'created_at <= ?' => $to . ' 23:59:59',
                    'email_imported is ?' => new Zend_Db_Expr('not null')
                );
            } else {
                $where = $conn->quoteInto(
                    'email_imported is ?', new Zend_Db_Expr('not null')
                );
            }

            $num  = $conn->update(
                $this->getMainTable(),
                array(
                    'email_imported' => new Zend_Db_Expr('null'),
                    'modified' => new Zend_Db_Expr('null')
                ),
                $where
            );

            return $num;
        } catch (Exception $e) {
            Mage::logException($e);
            return 0;
        }
    }

    /**
     * Join subscriber on collection
     *
     * @param Mage_Core_Model_Resource_Db_Collection_Abstract $collection
     * @param string $emailColumn
     *
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    public function joinSubscribersOnCollection($collection, $emailColumn = "main_table.customer_email")
    {
        $subscriberTable = Mage::getSingleton('core/resource')
            ->getTableName('newsletter_subscriber');
        $collection->getSelect()
            ->joinInner(
                array("st" => $subscriberTable),
                "st.subscriber_email = {$emailColumn}",
                array()
            )->where("st.subscriber_status = ?", Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
        return $collection;
    }
}
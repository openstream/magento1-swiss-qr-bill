<?php

declare(strict_types=1);

use Varien_Event_Observer as Transport;
use Mage_Core_Model_Email_Template as EmailTemplate;
use Mage_Sales_Model_Order as Order;

final class Openstream_QrBill_Model_Observer
{
    public function attachQrBill(Transport $transport)
    {
        if ($transport->getData('update') === true) {
            return;
        }

        $this->attachInvoice($transport->getData('template'), $transport->getData('object'));
    }

    private function attachInvoice(EmailTemplate $template, Order $order)
    {
        if (! $this->isBankPayment($order)) {
            return;
        }

        $template->getMail()->createAttachment(
            Mage::getModel('openstream_qrbill/bill')->createPdfForOrder($order),
            'application/pdf',
            Zend_Mime::DISPOSITION_ATTACHMENT,
            Zend_Mime::ENCODING_BASE64,
            sprintf('invoice-%s.pdf', $order->getData('increment_id'))
        );
    }

    private function isBankPayment(Order $order): bool
    {
        return $order->getPayment()->getData('method') === 'checkmo';
    }
}

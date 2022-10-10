<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Mage_Sales_Model_Order as Order;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\DataGroup\Element\CombinedAddress;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;
use Sprain\SwissQrBill\QrBill;
use Sprain\SwissQrBill\QrCode\QrCode;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;
use Sprain\SwissQrBill\Reference\RfCreditorReferenceGenerator;

final class Openstream_QrBill_Model_Bill
{
    public function createPdfForOrder(Order $order): string
    {
        $qrBill = QrBill::create();
        $qrBill->setCreditor(CombinedAddress::create(
            $this->getConfigValue('creditor_name'),
            $this->getConfigValue('creditor_address_line_1'),
            $this->getConfigValue('creditor_address_line_2'),
            'CH'
        ));
        $qrBill->setCreditorInformation(CreditorInformation::create($this->getConfigValue('iban')));
        $qrBill->setUltimateDebtor(StructuredAddress::createWithStreet(
            $order->getCustomerName(),
            $order->getBillingAddress()->getData('street'),
            $order->getBillingAddress()->getData('house_number'),
            $order->getBillingAddress()->getData('postcode'),
            $order->getBillingAddress()->getData('city'),
            $order->getBillingAddress()->getData('country_id')
        ));

        $qrBill->setPaymentAmountInformation(PaymentAmountInformation::create('CHF', (float) $order->getData('grand_total')));

        $qrBill->setPaymentReference($this->createPaymentReferenceForOrder($order));
        $qrBill->setAdditionalInformation(
            AdditionalInformation::create(Mage::helper('sales')->__('Order') . ' ' . $order->getIncrementId())
        );

        $output = new HtmlOutput($qrBill, $this->getLanguageCode($order));
        $output->setQrCodeImageFormat(QrCode::FILE_FORMAT_PNG);

        $dompdf = new Dompdf;
        $dompdf->loadHtml($this->fixHtml($output->getPaymentPart()));
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    private function getLanguageCode(Order $order): string
    {
        return substr(Mage::getStoreConfig('general/locale/code', $order->getData('store_id')), 0, 2);
    }

    private function getConfigValue(string $key): string
    {
        return (string) Mage::getStoreConfig('sales/qr_bill/' . $key);
    }

    private function createPaymentReferenceForOrder(Order $order): PaymentReference
    {
        if ($this->getConfigValue('is_legacy_iban')) {
            $referenceNumber = RfCreditorReferenceGenerator::generate($order->getData('increment_id'));

            return PaymentReference::create(PaymentReference::TYPE_SCOR, $referenceNumber);
        }

        $referenceNumber = QrPaymentReferenceGenerator::generate(null, $order->getData('increment_id'));

        return PaymentReference::create(PaymentReference::TYPE_QR, $referenceNumber);
    }

    private function fixHtml(string $html): string
    {
        $additionalCss = <<<EOF
#qr-bill-payment-part {
    display: table; 
} 

#qr-bill-payment-part-left {
    display: table-cell; 
    float: none;
} 

#qr-bill-payment-part-right {
    display: table-cell;
    padding-top: 11mm;
}
EOF;

        return preg_replace('/<\/style>/', $additionalCss . '</style>', $html);
    }
}

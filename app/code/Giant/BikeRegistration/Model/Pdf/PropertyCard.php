<?php
namespace Giant\BikeRegistration\Model\Pdf;

class PropertyCard
{
    public function generate($registration)
    {
        $regId = str_pad($registration->getId(), 6, '0', STR_PAD_LEFT);
        $name = $registration->getData('full_name');
        $docType = $registration->getData('document_type');
        $docNum = $registration->getData('document_number');
        $email = $registration->getData('email');
        $phone = $registration->getData('phone');
        $address = $registration->getData('address') ?: 'N/A';
        $city = $registration->getData('department_city');
        $bikeRef = $registration->getData('bike_reference');
        $serial = $registration->getData('serial_number');
        $storeName = $registration->getData('store_name');
        $purchaseDate = $registration->getData('purchase_date');
        $amount = number_format((float)$registration->getData('amount_paid'), 0, ',', '.');
        $invoiceNum = $registration->getData('invoice_number');
        $createdAt = $registration->getData('created_at');

        $pageW = 595;
        $pageH = 842;
        $margin = 50;
        $contentW = $pageW - (2 * $margin);

        $stream = '';
        $fonts = [];
        $fontId = 1;

        $helvetica = $fontId++;
        $helveticaBold = $fontId++;

        $objects = [];
        $objCount = 0;

        $addObj = function($content) use (&$objects, &$objCount) {
            $objCount++;
            $objects[$objCount] = $content;
            return $objCount;
        };

        $catalogId = $addObj('');
        $pagesId = $addObj('');

        $helvId = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>");
        $helvBId = $addObj("<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>");

        $y = $pageH - $margin;

        $contentStream = '';

        $contentStream .= "0.039 0.039 0.361 rg\n";
        $contentStream .= "0 " . ($pageH - 120) . " {$pageW} 120 re f\n";

        $contentStream .= "1 1 1 rg\n";
        $contentStream .= "BT /F2 24 Tf {$margin} " . ($pageH - 50) . " Td (GIANT) Tj ET\n";
        $contentStream .= "BT /F1 10 Tf {$margin} " . ($pageH - 70) . " Td (Authorized Brand Distributor) Tj ET\n";

        $rX = $pageW - $margin - 200;
        $contentStream .= "BT /F2 14 Tf {$rX} " . ($pageH - 50) . " Td (TARJETA DE PROPIEDAD) Tj ET\n";
        $contentStream .= "BT /F1 10 Tf {$rX} " . ($pageH - 70) . " Td (Registro No. GCO-{$regId}) Tj ET\n";
        $contentStream .= "BT /F1 9 Tf {$rX} " . ($pageH - 85) . " Td (Fecha: {$createdAt}) Tj ET\n";

        $y = $pageH - 155;
        $contentStream .= "0 0 0 rg\n";

        $contentStream .= "BT /F2 14 Tf {$margin} {$y} Td (DATOS DEL PROPIETARIO) Tj ET\n";
        $y -= 5;
        $contentStream .= "0.039 0.039 0.361 rg\n";
        $contentStream .= "{$margin} {$y} {$contentW} 2 re f\n";
        $contentStream .= "0 0 0 rg\n";
        $y -= 22;

        $fields1 = [
            ['Nombre Completo:', $name],
            ["{$docType}:", $docNum],
            ['Email:', $email],
            ['Telefono:', $phone],
            ['Direccion:', $address],
            ['Ciudad/Departamento:', $city],
        ];

        foreach ($fields1 as $f) {
            $label = $f[0];
            $value = $this->sanitize($f[1]);
            $contentStream .= "BT /F2 10 Tf {$margin} {$y} Td ({$label}) Tj ET\n";
            $contentStream .= "BT /F1 10 Tf 220 {$y} Td ({$value}) Tj ET\n";
            $y -= 18;
        }

        $y -= 15;
        $contentStream .= "BT /F2 14 Tf {$margin} {$y} Td (DATOS DE LA BICICLETA) Tj ET\n";
        $y -= 5;
        $contentStream .= "0.039 0.039 0.361 rg\n";
        $contentStream .= "{$margin} {$y} {$contentW} 2 re f\n";
        $contentStream .= "0 0 0 rg\n";
        $y -= 22;

        $fields2 = [
            ['Referencia:', $this->sanitize($bikeRef)],
            ['Numero de Serial:', $this->sanitize($serial)],
            ['Tienda:', $this->sanitize($storeName)],
            ['Fecha de Compra:', $purchaseDate],
            ['Valor Pagado:', "COP \${$amount}"],
            ['Numero de Factura:', $this->sanitize($invoiceNum)],
        ];

        foreach ($fields2 as $f) {
            $contentStream .= "BT /F2 10 Tf {$margin} {$y} Td ({$f[0]}) Tj ET\n";
            $contentStream .= "BT /F1 10 Tf 220 {$y} Td ({$f[1]}) Tj ET\n";
            $y -= 18;
        }

        $y -= 30;
        $contentStream .= "0.9 0.9 0.9 rg\n";
        $contentStream .= "{$margin} " . ($y - 10) . " {$contentW} 50 re f\n";
        $contentStream .= "0 0 0 rg\n";
        $y -= 5;
        $contentStream .= "BT /F1 8 Tf " . ($margin + 10) . " {$y} Td (Este documento certifica el registro de la bicicleta arriba descrita.) Tj ET\n";
        $y -= 14;
        $contentStream .= "BT /F1 8 Tf " . ($margin + 10) . " {$y} Td (Conserve este documento como comprobante de propiedad y registro de garantia.) Tj ET\n";
        $y -= 14;
        $contentStream .= "BT /F1 8 Tf " . ($margin + 10) . " {$y} Td (Giant Colombia - www.giant-bicycles.com.co) Tj ET\n";

        $streamLength = strlen($contentStream);
        $streamId = $addObj("<< /Length {$streamLength} >>\nstream\n{$contentStream}\nendstream");

        $resourceDict = "<< /Font << /F1 {$helvId} 0 R /F2 {$helvBId} 0 R >> >>";
        $pageId = $addObj("<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Contents {$streamId} 0 R /Resources {$resourceDict} >>");

        $objects[$pagesId] = "<< /Type /Pages /Kids [{$pageId} 0 R] /Count 1 >>";
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        for ($i = 1; $i <= $objCount; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= "{$i} 0 obj\n{$objects[$i]}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . ($objCount + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $objCount; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . ($objCount + 1) . " /Root {$catalogId} 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function sanitize($text)
    {
        $text = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $text ?: '');
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        return $text;
    }
}

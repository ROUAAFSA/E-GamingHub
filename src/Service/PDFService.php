<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PDFService
{
    private $dompdf;

    public function __construct()
    {
        $this->dompdf = new Dompdf();

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);
        
        $this->dompdf->setOptions($options);
    }

    public function generatePDF(string $html, string $filename): Response
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        $response = new Response(
            $this->dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );

        return $response;
    }
} 
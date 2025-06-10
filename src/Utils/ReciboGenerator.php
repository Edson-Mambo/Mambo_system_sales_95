<?php
namespace Src\Utils;

use Dompdf\Dompdf;

class ReciboGenerator
{
    public static function gerarRecibo(array $produtos, float $total, int $vendaId): string
    {
        $html = "<h2>Recibo de Venda #$vendaId</h2>";
        $html .= "<table border='1' cellpadding='5' cellspacing='0' width='100%'>";
        $html .= "<tr><th>Produto</th><th>Qtd</th><th>Preço</th><th>Total</th></tr>";

        foreach ($produtos as $item) {
            $produto = $item['produto'];
            $quantidade = $item['quantidade'];
            $preco = number_format($produto->getPreco(), 2);
            $subtotal = number_format($quantidade * $produto->getPreco(), 2);

            $html .= "<tr>
                <td>{$produto->getNome()}</td>
                <td>{$quantidade}</td>
                <td>{$preco}</td>
                <td>{$subtotal}</td>
            </tr>";
        }

        $html .= "<tr><td colspan='3'><strong>Total</strong></td><td><strong>" . number_format($total, 2) . "</strong></td></tr>";
        $html .= "</table>";
        $html .= "<p>Data: " . date('d/m/Y H:i') . "</p>";

        // Gera o PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $filename = 'recibo_' . $vendaId . '.pdf';
        $filePath = __DIR__ . '/../../public/recibos/' . $filename;
        file_put_contents($filePath, $output);

        return '/public/recibos/' . $filename; // Caminho público
    }
}

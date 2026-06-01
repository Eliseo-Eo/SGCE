<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

class SgcePdfSimple {
    private array $Pages = [];
    private array $Current = [];
    private int $Width = 612;
    private int $Height = 792;
    private string $Orientation = 'P';
    private float $X = 36;
    private float $Y = 36;
    private float $Margin = 36;
    private int $FontSize = 10;
    private string $Font = 'F1';
    private array $Stroke = [0, 0, 0];
    private array $Fill = [0, 0, 0];

    public function __construct(string $Orientation = 'P') {
        $this->AddPage($Orientation);
    }

    public function AddPage(string $Orientation = 'P'): void {
        if ($this->Current) { $this->Pages[] = $this->Current; }
        $this->Orientation = strtoupper($Orientation) === 'L' ? 'L' : 'P';
        $this->Width = $this->Orientation === 'L' ? 792 : 612;
        $this->Height = $this->Orientation === 'L' ? 612 : 792;
        $this->X = $this->Margin;
        $this->Y = $this->Margin;
        $this->Current = ['w' => $this->Width, 'h' => $this->Height, 'ops' => []];
    }

    public function Width(): int { return $this->Width; }
    public function Height(): int { return $this->Height; }
    public function Margin(): float { return $this->Margin; }
    public function Y(): float { return $this->Y; }
    public function SetY(float $Y): void { $this->Y = $Y; }
    public function AddY(float $Delta): void { $this->Y += $Delta; }

    private function Op(string $Op): void { $this->Current['ops'][] = $Op; }

    private function PdfY(float $Y): float { return $this->Height - $Y; }

    private function Enc(string $Text): string {
        $Text = str_replace(["\r\n", "\r"], "\n", (string)$Text);
        $Text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $Text);
        if ($Text === false) { $Text = ''; }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $Text);
    }

    public function SetTextColorHex(string $Hex): void {
        $this->Fill = $this->HexToRgb($Hex);
    }

    public function SetDrawColorHex(string $Hex): void {
        $this->Stroke = $this->HexToRgb($Hex);
    }

    private function HexToRgb(string $Hex): array {
        $Hex = ltrim(trim($Hex), '#');
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $Hex)) { $Hex = '000000'; }
        return [hexdec(substr($Hex,0,2)), hexdec(substr($Hex,2,2)), hexdec(substr($Hex,4,2))];
    }

    private function Color(array $Rgb, bool $Fill = true): string {
        $Vals = array_map(fn($V) => number_format(max(0, min(255, (int)$V)) / 255, 3, '.', ''), $Rgb);
        return implode(' ', $Vals) . ($Fill ? ' rg' : ' RG');
    }

    public function Text(float $X, float $Y, string $Text, int $Size = 10, bool $Bold = false, string $ColorHex = '#111827'): void {
        $Font = $Bold ? 'F2' : 'F1';
        $Rgb = $this->HexToRgb($ColorHex);
        $this->Op('BT ' . $this->Color($Rgb, true) . " /{$Font} {$Size} Tf 1 0 0 1 " . $this->Fmt($X) . ' ' . $this->Fmt($this->PdfY($Y)) . ' Tm (' . $this->Enc($Text) . ') Tj ET');
    }

    public function Line(float $X1, float $Y1, float $X2, float $Y2, string $ColorHex = '#111827', float $Width = 1): void {
        $Rgb = $this->HexToRgb($ColorHex);
        $this->Op($this->Color($Rgb, false) . ' ' . $this->Fmt($Width) . ' w ' . $this->Fmt($X1) . ' ' . $this->Fmt($this->PdfY($Y1)) . ' m ' . $this->Fmt($X2) . ' ' . $this->Fmt($this->PdfY($Y2)) . ' l S');
    }

    public function Rect(float $X, float $Y, float $W, float $H, string $FillHex = '#FFFFFF', string $StrokeHex = '#E5E7EB', float $LineWidth = .6): void {
        $Fill = $this->HexToRgb($FillHex);
        $Stroke = $this->HexToRgb($StrokeHex);
        $this->Op($this->Color($Fill, true) . ' ' . $this->Color($Stroke, false) . ' ' . $this->Fmt($LineWidth) . ' w ' . $this->Fmt($X) . ' ' . $this->Fmt($this->PdfY($Y + $H)) . ' ' . $this->Fmt($W) . ' ' . $this->Fmt($H) . ' re B');
    }

    private function ULen(string $Text): int { return function_exists('mb_strlen') ? mb_strlen($Text, 'UTF-8') : strlen($Text); }

    private function USubstr(string $Text, int $Start, int $Length = null): string {
        if (function_exists('mb_substr')) { return $Length === null ? mb_substr($Text, $Start, null, 'UTF-8') : mb_substr($Text, $Start, $Length, 'UTF-8'); }
        return $Length === null ? substr($Text, $Start) : substr($Text, $Start, $Length);
    }

    public function WrapText(string $Text, float $MaxWidth, int $Size = 10): array {
        $Text = trim(preg_replace('/\s+/u', ' ', $Text));
        if ($Text === '') { return ['']; }
        $MaxChars = max(8, (int)floor($MaxWidth / ($Size * .52)));
        $Words = preg_split('/\s+/u', $Text) ?: [];
        $Lines = [];
        $Line = '';
        foreach ($Words as $Word) {
            $Candidate = trim($Line . ' ' . $Word);
            if ($this->ULen($Candidate) > $MaxChars && $Line !== '') {
                $Lines[] = $Line;
                $Line = $Word;
            } else {
                $Line = $Candidate;
            }
        }
        if ($Line !== '') { $Lines[] = $Line; }
        return $Lines ?: [''];
    }

    public function MultiText(float $X, float $Y, float $MaxWidth, string $Text, int $Size = 10, bool $Bold = false, string $ColorHex = '#111827', float $LineHeight = 13, int $MaxLines = 0): float {
        $Lines = $this->WrapText($Text, $MaxWidth, $Size);
        if ($MaxLines > 0 && count($Lines) > $MaxLines) {
            $Lines = array_slice($Lines, 0, $MaxLines);
            $Last = count($Lines) - 1;
            $Lines[$Last] = rtrim($this->USubstr($Lines[$Last], 0, max(0, $this->ULen($Lines[$Last]) - 1))) . '...';
        }
        foreach ($Lines as $I => $Line) { $this->Text($X, $Y + ($I * $LineHeight), $Line, $Size, $Bold, $ColorHex); }
        return max($LineHeight, count($Lines) * $LineHeight);
    }

    public function HeaderBlock(string $Title, string $Subtitle, string $School, string $ColorHex = '#97051E'): void {
        $X = $this->Margin;
        $Y = 30;
        $W = $this->Width - ($this->Margin * 2);
        $this->Rect($X, $Y, $W, 76, $ColorHex, $ColorHex, 0);
        $this->Text($X + 18, $Y + 25, $Title, 22, true, '#FFFFFF');
        if ($School !== '') { $this->Text($X + 18, $Y + 45, $School, 9, true, '#FFFFFF'); }
        $this->MultiText($X + 18, $Y + 58, $W - 36, $Subtitle, 9, false, '#FFFFFF', 11, 2);
        $this->Y = $Y + 96;
    }

    public function Table(array $Headers, array $Rows, array $Widths, string $ColorHex = '#97051E', array $Options = []): void {
        $X = $this->Margin;
        $Top = $this->Y;
        $RowHeight = $Options['row_height'] ?? 18;
        $HeaderHeight = $Options['header_height'] ?? 22;
        $FontSize = $Options['font_size'] ?? 8;
        $RepeatHeader = function() use ($X, $Headers, $Widths, $HeaderHeight, $ColorHex) {
            $Cx = $X;
            foreach ($Headers as $I => $Head) {
                $this->Rect($Cx, $this->Y, $Widths[$I], $HeaderHeight, $ColorHex, $ColorHex, .5);
                $this->MultiText($Cx + 4, $this->Y + 8, $Widths[$I] - 8, (string)$Head, 7, true, '#FFFFFF', 8, 2);
                $Cx += $Widths[$I];
            }
            $this->Y += $HeaderHeight;
        };
        $RepeatHeader();
        $Index = 0;
        foreach ($Rows as $Row) {
            if ($this->Y + $RowHeight > $this->Height - 46) {
                $this->AddFooter();
                $this->AddPage($this->Orientation);
                $this->Y = 36;
                $RepeatHeader();
            }
            $Fill = ($Index % 2 === 0) ? '#FFFFFF' : '#F9FAFB';
            $Cx = $X;
            foreach ($Headers as $I => $_) {
                $Texto = (string)($Row[$I] ?? '');
                $this->Rect($Cx, $this->Y, $Widths[$I], $RowHeight, $Fill, '#E5E7EB', .35);
                $this->MultiText($Cx + 4, $this->Y + 12, $Widths[$I] - 8, $Texto, $FontSize, false, '#111827', 9, 2);
                $Cx += $Widths[$I];
            }
            $this->Y += $RowHeight;
            $Index++;
        }
        if (!$Rows) {
            $W = array_sum($Widths);
            $this->Rect($X, $this->Y, $W, 28, '#FFFFFF', '#E5E7EB', .35);
            $this->Text($X + 8, $this->Y + 18, 'Sin registros disponibles.', 9, true, '#6B7280');
            $this->Y += 28;
        }
    }

    public function AddFooter(): void {
        $PageNumber = count($this->Pages) + 1;
        $this->Line($this->Margin, $this->Height - 28, $this->Width - $this->Margin, $this->Height - 28, '#E5E7EB', .5);
        $this->Text($this->Margin, $this->Height - 14, 'SGCE - Generado el ' . date('d/m/Y H:i'), 7, false, '#6B7280');
        $this->Text($this->Width - $this->Margin - 50, $this->Height - 14, 'Página ' . $PageNumber, 7, false, '#6B7280');
    }

    private function Fmt(float $Number): string { return rtrim(rtrim(number_format($Number, 2, '.', ''), '0'), '.'); }

    public function Output(string $Filename): void {
        if ($this->Current) { $this->AddFooter(); $this->Pages[] = $this->Current; $this->Current = []; }
        $Objects = [];
        $Objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $Kids = [];
        $PageObjNumbers = [];
        $ContentObjNumbers = [];
        $Base = 3;
        foreach ($this->Pages as $I => $Page) {
            $PageObjNumbers[] = $Base + ($I * 2);
            $ContentObjNumbers[] = $Base + ($I * 2) + 1;
            $Kids[] = ($Base + ($I * 2)) . ' 0 R';
        }
        $Objects[] = '<< /Type /Pages /Kids [' . implode(' ', $Kids) . '] /Count ' . count($Kids) . ' >>';
        foreach ($this->Pages as $I => $Page) {
            $Content = implode("\n", $Page['ops']);
            $Objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . (int)$Page['w'] . ' ' . (int)$Page['h'] . '] /Resources << /Font << /F1 ' . (count($this->Pages) * 2 + 3) . ' 0 R /F2 ' . (count($this->Pages) * 2 + 4) . ' 0 R >> >> /Contents ' . $ContentObjNumbers[$I] . ' 0 R >>';
            $Objects[] = '<< /Length ' . strlen($Content) . " >>\nstream\n" . $Content . "\nendstream";
        }
        $Objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $Objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $Pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $Offsets = [0];
        foreach ($Objects as $I => $Obj) {
            $Offsets[] = strlen($Pdf);
            $Pdf .= ($I + 1) . " 0 obj\n" . $Obj . "\nendobj\n";
        }
        $Xref = strlen($Pdf);
        $Pdf .= "xref\n0 " . (count($Objects) + 1) . "\n0000000000 65535 f \n";
        for ($I = 1; $I <= count($Objects); $I++) { $Pdf .= sprintf('%010d 00000 n ', $Offsets[$I]) . "\n"; }
        $Pdf .= "trailer\n<< /Size " . (count($Objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $Xref . "\n%%EOF";
        if (ob_get_length()) { ob_end_clean(); }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($Filename) . '"');
        header('Content-Length: ' . strlen($Pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $Pdf;
        exit;
    }
}

function SgcePdfArchivoSeguro(string $Texto): string {
    $Texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $Texto);
    $Texto = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string)$Texto);
    $Texto = trim($Texto, '_');
    return $Texto !== '' ? $Texto : 'Reporte_SGCE';
}

function SgcePdfRespuestaTabla(PDO $Pdo, string $Titulo, string $Subtitulo, array $Columnas, array $Filas, string $Archivo, string $Orientacion = 'P', array $Anchos = []): void {
    $Config = SgceObtenerConfiguracion($Pdo);
    $Color = SgceColorInstitucional($Pdo);
    $Pdf = new SgcePdfSimple($Orientacion);
    $Pdf->HeaderBlock($Titulo, $Subtitulo, (string)($Config['NombreEscuela'] ?? ''), $Color);
    if (!$Anchos) {
        $Disponible = $Pdf->Width() - ($Pdf->Margin() * 2);
        $Anchos = array_fill(0, count($Columnas), $Disponible / max(1, count($Columnas)));
    }
    $Pdf->Table($Columnas, $Filas, $Anchos, $Color, ['font_size' => $Orientacion === 'L' ? 7 : 8]);
    $Pdf->Output(SgcePdfArchivoSeguro($Archivo) . '.pdf');
}

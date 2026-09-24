<?php

declare(strict_types=1);

use Magmi\Gui\App\Router;
use Magmi\Gui\App\View;
use Magmi\Gui\Auth\Csrf;
use Magmi\Gui\Controller\ImportController;
use PHPUnit\Framework\TestCase;

final class ImportWorkflowTest extends TestCase
{
    protected function setUp(): void { $_SESSION = []; $_POST = []; }

    public function testCsrfRequiresMatchingSessionToken(): void
    {
        self::assertFalse(Csrf::validate(null));
        $token = Csrf::token();
        self::assertSame($token, Csrf::token());
        self::assertTrue(Csrf::validate($token));
        self::assertFalse(Csrf::validate('wrong'));
        self::assertFalse(Csrf::validate([$token]));
    }

    public function testAuthenticatedMutationWithoutTokenIsRejected(): void
    {
        $_SESSION['magmi_authenticated'] = true;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/config/save';
        ob_start();
        (new Router())->dispatch();
        $response = ob_get_clean();
        self::assertSame(403, http_response_code());
        self::assertStringContainsString('Invalid form token', $response);
        self::assertFileDoesNotExist(MAGMI_GUI_BASE_DIR . '/data/config/global.json');
    }

    public function testTsvPreviewMatchesImportReader(): void
    {
        $file = MAGMI_GUI_BASE_DIR . '/preview.tsv';
        file_put_contents($file, "\xEF\xBB\xBFsku\tname\nA\tPreview\n");
        $method = new ReflectionMethod(ImportController::class, 'readPreviewRows');
        $rows = $method->invoke(new ImportController(), $file, ['separator' => ',']);
        self::assertSame([['sku', 'name'], ['A', 'Preview']], $rows);
    }

    public function testExcelPreviewMatchesImportReader(): void
    {
        $file = MAGMI_GUI_BASE_DIR . '/preview.xlsx';
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $book->getActiveSheet()->fromArray([['sku', 'name'], ['B', 'Excel']]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($file);
        $book->disconnectWorksheets();
        $method = new ReflectionMethod(ImportController::class, 'readPreviewRows');
        self::assertSame([['sku', 'name'], ['B', 'Excel']], $method->invoke(new ImportController(), $file, []));
    }

    public function testErrorStringIsNotRenderedAsSuccess(): void
    {
        ob_start();
        (new View())->render('import', ['currentProfile' => null, 'report' => ['status' => 'error', 'message' => 'Database unavailable']]);
        $html = ob_get_clean();
        self::assertStringContainsString('Database unavailable', $html);
        self::assertStringContainsString('text-danger', $html);
        self::assertStringNotContainsString('>OK</span>', $html);
        self::assertStringContainsString('name="csrf_token"', $html);
    }
}

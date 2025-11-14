<?php
namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class ImportController extends BaseController
{
    /**
     * Trigger a CSV import or schedule an import job. Expected JSON: { import_name: string, mode: 'dry'|'apply' }
     */
    public function trigger(Request $request)
    {
        $data = $request->only(['import_name','mode']);

        if (empty($data['import_name'])) {
            return response()->json(['error' => 'Missing import_name'], 422);
        }

        // TODO: enqueue an import job or call import adapter; for now return stubbed response.
        return response()->json(['ok' => true, 'import' => $data]);
    }
}

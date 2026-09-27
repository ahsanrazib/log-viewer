<?php

namespace SolverCircle\LogViewer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use SolverCircle\LogViewer\Services\LogViewerService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogViewerController extends Controller
{
    public function __construct(
        protected LogViewerService $service
    ) {}

    /**
     * Render Log Viewer UI interface.
     */
    public function index(Request $request)
    {
        if (! Config::get('log-viewer.enabled', true)) {
            abort(404);
        }

        $file = $request->input('file');
        $levelsInput = $request->input('levels') ?? $request->input('level', 'all');
        $query = $request->input('q');
        $page = (int) $request->input('page', 1);

        $data = $this->service->getLogs(
            fileName: $file,
            level: $levelsInput,
            query: $query,
            page: $page
        );

        $levelsArray = is_array($levelsInput)
            ? $levelsInput
            : (is_string($levelsInput) && trim($levelsInput) !== '' ? explode(',', $levelsInput) : ['all']);

        $data['current_level'] = is_array($levelsInput) ? implode(',', $levelsInput) : $levelsInput;
        $data['current_levels'] = array_map('strtoupper', array_map('trim', $levelsArray));
        $data['search_query'] = $query;
        $data['theme'] = Config::get('log-viewer.theme', 'auto');
        $data['route_prefix'] = Config::get('log-viewer.route_prefix', 'log-viewer');
        $data['has_passkey'] = ! empty(Config::get('log-viewer.passkey'));

        return view('log-viewer::index', $data);
    }

    /**
     * Authenticate user passkey.
     */
    public function authenticatePasskey(Request $request)
    {
        $expectedPasskey = Config::get('log-viewer.passkey');
        $inputPasskey = $request->input('passkey');

        if (! empty($expectedPasskey) && $inputPasskey === $expectedPasskey) {
            $request->session()->put('log_viewer_passkey_authorized', true);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Passkey authenticated.']);
            }

            return redirect()->route('log-viewer.index');
        }

        if ($request->wantsJson()) {
            return response()->json(['error' => 'Invalid passkey.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid passkey provided.');
    }

    /**
     * Lock passkey session.
     */
    public function lockPasskey(Request $request)
    {
        $request->session()->forget('log_viewer_passkey_authorized');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Passkey locked.']);
        }

        return redirect()->route('log-viewer.index');
    }

    /**
     * API endpoint for AJAX live polling / auto-refresh.
     */
    public function api(Request $request): JsonResponse
    {
        if (! Config::get('log-viewer.enabled', true)) {
            return response()->json(['error' => 'Log viewer disabled'], 404);
        }

        $file = $request->input('file');
        $levelsInput = $request->input('levels') ?? $request->input('level', 'all');
        $query = $request->input('q');
        $page = (int) $request->input('page', 1);

        $data = $this->service->getLogs(
            fileName: $file,
            level: $levelsInput,
            query: $query,
            page: $page
        );

        return response()->json($data);
    }

    /**
     * Download specified log file.
     */
    public function download(string $file): BinaryFileResponse
    {
        if (! Config::get('log-viewer.enabled', true)) {
            abort(404);
        }

        $path = $this->service->resolveFilePath($file);

        return response()->download($path, basename($path), [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Clear log file contents.
     */
    public function clear(Request $request, string $file)
    {
        if (! Config::get('log-viewer.enabled', true)) {
            abort(404);
        }

        $cleared = $this->service->clearLogFile($file);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $cleared,
                'message' => $cleared ? "Log file '{$file}' cleared successfully." : "Failed to clear log file '{$file}'.",
            ]);
        }

        return redirect()->back()->with('status', "Log file '{$file}' cleared successfully.");
    }

    /**
     * Delete log file.
     */
    public function delete(Request $request, string $file)
    {
        if (! Config::get('log-viewer.enabled', true)) {
            abort(404);
        }

        $deleted = $this->service->deleteLogFile($file);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? "Log file '{$file}' deleted successfully." : "Failed to delete log file '{$file}'.",
            ]);
        }

        return redirect()->route('log-viewer.index')->with('status', "Log file '{$file}' deleted successfully.");
    }
}

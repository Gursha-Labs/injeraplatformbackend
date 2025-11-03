<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Stancl\Tenancy\Facades\Tenancy;


class FileUploadController extends Controller
{
    public function uploadFile(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|file',
        'dzchunkindex' => 'sometimes|integer',
        'dztotalchunkcount' => 'sometimes|integer',
        'fileName' => 'required|string',
    ]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }
    $chunkIndex = intval($request->input('dzchunkindex'));
    $totalChunks = intval($request->input('dztotalchunkcount'));
    $file = $request->file('file');
    $fileName = $request->input('fileName');
    if ($totalChunks <= 1) {
        $finalPath = 'uploads/' . $fileName;
        $file->storeAs('', $finalPath, 'public'); 
        $url = Storage::disk('public')->url($finalPath);
        return response()->json(['url' => $fileName], 200);
    }
    $chunkFolder = 'uploads/chunks/' . md5($fileName);
    $chunkPath = $chunkFolder . '/chunk-' . $chunkIndex;
    
    Storage::disk('public')->put($chunkPath, file_get_contents($file->getRealPath()));

    if ($chunkIndex + 1 === $totalChunks) {
        $finalPath = 'uploads/' . $fileName;
        $this->mergeChunks($chunkFolder, $totalChunks, $finalPath);
        $url = Storage::disk('public')->url($finalPath);
        $this->cleanupChunks($chunkFolder, $totalChunks);
        return response()->json(['url' => $fileName], 200);
    }

    return response()->json(['status' => 'Chunk uploaded successfully'], 200);
}

    // public function uploadFile(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|file', // Adjust file types and max size as needed
    //         'chunkIndex' => 'required', // Chunk index for each part
    //         'totalChunks' => 'required', // Total number of chunks
    //         'fileName' => 'required|string', // Original file name
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     $chunkIndex = intval($request->input('dzchunkindex'));
    //     $totalChunks = intval($request->input('dztotalchunkcount'));
    //     $file = $request->file('file');
    //     $fileName = $request->input('fileName');

    //     // Handle single-chunk files
    //     if ($totalChunks <= 1) {
    //         $finalFilePath = $fileName;
    //         $file->storeAs('uploads', $finalFilePath, 'public');
    //         $url = Storage::url($finalFilePath);
    //         return response()->json(['url' => $finalFilePath], 200);
    //     }

    //     // Handle multi-chunk files
    //     $fileFolder = 'uploads/chunks/' . md5($fileName);
    //     $filePath = $fileFolder . '/chunk-' . $chunkIndex;
    //     $file->storeAs('public', $filePath);

    //     if ($chunkIndex + 1 === $totalChunks) {
    //         $finalFilePath = 'uploads/' . $fileName;
    //         $this->mergeChunks($fileFolder, $totalChunks, $finalFilePath);
    //         // $url = Storage::url($finalFilePath);
    //         $url = tenant_asset($finalFilePath);
    //         $this->cleanupChunks($fileFolder, $totalChunks);
    //         return response()->json(['url' => $finalFilePath], 200);
    //     }

    //     return response()->json(['status' => 'Chunk uploaded successfully'], 200);
    // }

    public function retrieveFile(Request $request)
    {
        $tenant = tenant(); // Ensure the tenant is active
        $filePath = $request['file_Url'];
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
        // dd(storage_path($filePath));
        $storageDisk = Storage::disk('public'); // Ensure tenant disk is configured
        
        // Check if the file exists
        if ($storageDisk->exists($filePath)) {
            // Generate the URL (if needed for serving in the browser)
            return response()->file(storage_path("app/public/{$filePath}"));
            $fileUrl = $storageDisk->url($filePath);
            return response()->json(['url' => $fileUrl], 200);
        }
        
        return response()->json(['error' => 'File not found'], 404);
    }



    // public function uploadFile(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|file', // Adjust file types and max size as needed
    //         'chunkIndex' => 'required', // Chunk index for each part
    //         'totalChunks' => 'required', // Total number of chunks
    //         'fileName' => 'required|string', // Original file name
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }
    //     $chunkIndex = intval($request->input('dzchunkindex'));
    //     $totalChunks = intval($request->input('dztotalchunkcount'));
    //     $file = $request->file('file');
    //     $fileName = $request->input('fileName');
    //     $fileFolder = 'uploads/chunks/' . md5($fileName);
    //     $filePath = $fileFolder . '/chunk-' . $chunkIndex;
    //     $file->storeAs('public', $filePath);
    //     if ($chunkIndex + 1 === $totalChunks) {
    //         $finalFilePath = 'uploads/' . $fileName;
    //         $this->mergeChunks($fileFolder, $totalChunks, $finalFilePath);
    //         $url = Storage::url($finalFilePath);
    //         $this->cleanupChunks($fileFolder, $totalChunks);
    //         return response()->json(['url' => $finalFilePath], 200);
    //     }
    //     return response()->json(['status' => 'Chunk uploaded successfully'], 200);
    // }

    public function uploadFileOld(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'file' => 'required|file', // Adjust file types and max size as needed
            'chunkIndex' => 'required', // Chunk index for each part
            'totalChunks' => 'required', // Total number of chunks
            'fileName' => 'required|string', // Original file name
        ]);

        $chunk = $request->file('file');
        $filename = $request->input('fileName');
        $chunkIndex = intval($request->input('chunkIndex'));
        $totalChunks = intval($request->input('totalChunks'));
        $location = "data";
        $tempDir = storage_path('app/temp_chunks/' . $filename);
        $finalDir = storage_path('app/public/uploads/' . $location);
        $finalPath = $finalDir . '/' . $filename;

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $chunk->move($tempDir, $chunkIndex);
        $files = scandir($tempDir);
        if (count($files) - 2 === (int) $totalChunks) {
            if (!file_exists($finalDir)) {
                mkdir($finalDir, 0777, true);
            }
            $output = fopen($finalPath, 'wb');
            if (!$output) {
                return response()->json(['message' => 'Failed to open final file path'], 500);
            }
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/' . $i;
                $chunkFile = fopen($chunkPath, 'rb');
                if ($chunkFile) {
                    stream_copy_to_stream($chunkFile, $output);
                    fclose($chunkFile);
                } else {
                    return response()->json(['message' => 'Failed to open chunk file'], 500);
                }
            }
            fclose($output);
            array_map('unlink', glob("$tempDir/*"));
            rmdir($tempDir);
            return response()->json(['url' => 'storage/uploads/' . $location . '/'. $filename], 200);
        }

        return response()->json(['message' => 'Chunk uploaded successfully.']);
    }

    protected function mergeChunks($folder, $totalChunks, $finalPath)
    {
        // Open the final file for writing
        $finalFile = fopen(storage_path('app/public/' . $finalPath), 'wb');
        // Append each chunk to the final file
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = storage_path('app/public/' . $folder . '/chunk-' . $i);
            if (File::exists($chunkPath)) {
                $chunk = fopen($chunkPath, 'rb');
                while ($data = fread($chunk, 1024)) {
                    fwrite($finalFile, $data);
                }
                fclose($chunk);
                File::delete($chunkPath); // Optionally delete the chunk after it's merged
            }
        }

        fclose($finalFile);
    }

    protected function cleanupChunks($folder, $totalChunks)
    {
        // Delete the chunk folder after the merge
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = storage_path('app/public/' . $folder . '/chunk-' . $i);
            if (File::exists($chunkPath)) {
                File::delete($chunkPath);
            }
        }

        File::deleteDirectory(storage_path('app/public/' . $folder));
    }
}

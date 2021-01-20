<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Storage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoragesStoreRequest;

class StoragesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $size = '';
        $types = '';
        $supported_os = '';
        $deleted_at = '';

        $storages = Storage::all();

        return view('storages.index', [
            'storages' => $storages,
            'size' => $size,
            'types' => $types,
            'supported_os' => $supported_os,
            'deleted_at' => $deleted_at,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('storages.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoragesStoreRequest $request)
    {
        $by_id = Auth::user()->id;

        $new_storage = Storage::create([
            'maker' => $request->maker,
            'model_number' => $request->model_number,
            'serial_number' => $request->serial_number,
            'size' => $request->size,
            'types' => $request->types,
            'supported_os' => $request->supported_os,
            'recovery_key' => $request->recovery_key,
            'password' => $request->password,
            'deleted_at' => $request->deleted_at ? $now : null,
            'reason' => $request->reason,
            'updated_by' => $by_id,
            'created_by' => $by_id,
        ]);

        return redirect()->route('storages.show', $new_storage->id)->with('information', 'レコードを作成しました。');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $storage = Storage::with('opportunityRelations')->findOrFail($id);

        return view('storages.show', [
            'storage' => $storage,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $storage = Storage::find($id);

        return view('storages.edit', [
            'storage' => $storage,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $now = \Carbon\Carbon::now();
        $storage = Storage::find($id);
        $by_id = Auth::user()->id;

        $storage->fill([
            'maker' => $request->maker,
            'model_number' => $request->model_number,
            'serial_number' => $request->serial_number,
            'size' => $request->size,
            'types' => $request->types,
            'supported_os' => $request->supported_os,
            'recovery_key' => $request->recovery_key,
            'password' => $request->password,
            'deleted_at' => $request->deleted_at ? $now : null,
            'reason' => $request->reason,
            'updated_by' => $by_id,
        ])
        ->save();

        return redirect()->route('storages.show', $storage->id)->with('information', 'レコードを更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function search(Request $request)
    {
        $size = $request->size;
        $types = $request->types;
        $supported_os = $request->supported_os;
        $deleted_at = $request->deleted_at;

        $query = Storage::query();

        if(isset($size)) {
            $query->where('size', 'like', '%' . $size . '%');
        }
        if(isset($types)) {
            $query->where('types', $types);
        }
        if(isset($supported_os)) {
            $query->where('supported_os', $supported_os);
        }
        if(isset($deleted_at)) {
            if($deleted_at == 1) {
                $query->whereNotNull('deleted_at');
            } elseif ($deleted_at == 0) {
                $query->whereNull('deleted_at');
            }
        }

        $storages = $query->get();

        return view('storages.index', [
            'storages' => $storages,
            'size' => $size,
            'types' => $types,
            'supported_os' => $supported_os,
            'deleted_at' => $deleted_at,
        ]);
    }

    public function csv(Request $request)
    {
        // ヘッダー生成
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=storages.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        // データ取得＆生成
        $query = Storage::query();

        $size = $request->size;
        $types = $request->types;
        $supported_os = $request->supported_os;
        $deleted_at = $request->deleted_at;

        if(isset($size)) {
            $query->where('size', 'like', '%' . $size . '%');
        }
        if(isset($types)) {
            $query->where('types', $types);
        }
        if(isset($supported_os)) {
            $query->where('supported_os', $supported_os);
        }
        if(isset($deleted_at)) {
            if($deleted_at == 1) {
                $query->whereNotNull('deleted_at');
            } elseif ($deleted_at == 0) {
                $query->whereNull('deleted_at');
            }
        }

        $storages = $query->get();
        $this->storages = $storages;

        $callback = function()
        {
            $createCsvFile = fopen('php://output', 'w'); //ファイル作成
            
            $columns = [ //1行目の情報
                'id',
                'maker',
                'model_number',
                'serial_number',
                'size',
                'types',
                'supported_os',
                'recovery_key',
                'password',
                'created_at',
                'created_by',
                'updated_at',
                'updated_by',
                'deleted_at',
                'reason',
            ];

            mb_convert_variables('SJIS-win', 'UTF-8', $columns); //文字化け対策
    
            fputcsv($createCsvFile, $columns); //1行目の情報を追記

            $storages = $this->storages;

            foreach ($storages as $storage) {  //データを1行ずつ回す
                $csv = [
                    $storage->id,  //オブジェクトなので -> で取得
                    $storage->maker,
                    $storage->model_number,
                    $storage->serial_number,
                    $storage->size,
                    $storage->types,
                    $storage->supported_os,
                    $storage->recovery_key,
                    $storage->password,
                    $storage->created_at,
                    $storage->created_by,
                    $storage->updated_at,
                    $storage->deleted_at,
                    $storage->deleted_at,
                    $storage->reason,
                ];

                mb_convert_variables('SJIS-win', 'UTF-8', $csv); //文字化け対策

                fputcsv($createCsvFile, $csv); //ファイルに追記する
            }
            fclose($createCsvFile); //ファイル閉じる
        };
        
        return response()->stream($callback, 200, $headers);
    }
}

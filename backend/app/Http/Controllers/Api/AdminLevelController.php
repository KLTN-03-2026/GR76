<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MucDoKhanCap;
use App\Http\Requests\StoreMucDoRequest;
use Illuminate\Http\Request;

class AdminLevelController extends Controller
{
    public function index()
    {
        return response()->json(['data' => MucDoKhanCap::all()]);
    }

    public function store(StoreMucDoRequest $request)
    {
        $level = MucDoKhanCap::create($request->validated());
        return response()->json(['message' => 'Thêm thành công', 'data' => $level], 201);
    }

    public function update(Request $request, $id)
    {
        $level = MucDoKhanCap::findOrFail($id);
        $data = $request->validate([
            'ten_muc_do' => 'sometimes|required|string|max:255|unique:muc_do_khan_caps,ten_muc_do,' . $id . ',id_muc_do',
            'do_uu_tien' => 'sometimes|required|integer|min:0'
        ]);
        $level->update($data);
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $level]);
    }

    public function destroy($id)
    {
        $level = MucDoKhanCap::findOrFail($id);
        $level->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }
}

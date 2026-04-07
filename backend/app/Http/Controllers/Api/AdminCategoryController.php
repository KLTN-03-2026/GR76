<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoaiSuCo;
use App\Http\Requests\StoreLoaiSuCoRequest;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function index()
    {
        return response()->json(['data' => LoaiSuCo::all()]);
    }

    public function store(StoreLoaiSuCoRequest $request)
    {
        $category = LoaiSuCo::create($request->validated());
        return response()->json(['message' => 'Thêm thành công', 'data' => $category], 201);
    }

    public function update(Request $request, $id)
    {
        $category = LoaiSuCo::findOrFail($id);
        $data = $request->validate([
            'ten_loai' => 'required|string|max:255|unique:loai_su_cos,ten_loai,' . $id . ',id_loai_su_co'
        ]);
        $category->update($data);
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $category]);
    }

    public function destroy($id)
    {
        $category = LoaiSuCo::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Xoá thành công']);
    }
}

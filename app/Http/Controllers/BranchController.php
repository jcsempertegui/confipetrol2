<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branche;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function switchBranch(Request $request)
    {
        try {
            $branchId = $request->input('branch_id');
            
            // Verificar que la sucursal existe y está activa
            $branch = Branche::where('id', $branchId)
                           ->where('status', 1)
                           ->first();
            
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sucursal no encontrada'
                ], 404);
            }
            
            // Actualizar la sesión con la nueva sucursal
            session(['branch_user_id' => $branchId]);
            
            return response()->json([
                'success' => true,
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name
                ],
                'message' => 'Sucursal cambiada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar sucursal: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getCurrentBranch()
    {
        $branchId = session('branch_user_id', auth()->user()->branch_id);
        $branch = Branche::find($branchId);
        
        return response()->json([
            'success' => true,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name
            ] : null
        ]);
    }
}
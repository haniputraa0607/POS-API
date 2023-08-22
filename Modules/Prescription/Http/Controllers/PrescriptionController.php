<?php

namespace Modules\Prescription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Lib\MyHelper;
use Modules\Prescription\Entities\Prescription;
use Modules\Prescription\Entities\PrescriptionOutlet;
use Modules\Prescription\Entities\PrescriptionOutletLog;
use Modules\Prescription\Entities\PrescriptionCategory;
use Modules\Order\Entities\Order;
use Modules\Prescription\Entities\CategoryContainer;
use Modules\Prescription\Entities\Container;
use Modules\Prescription\Entities\ContainerStockLog;
use Modules\Prescription\Entities\CategorySubstance;
use Modules\Prescription\Entities\Substance;
use Modules\Prescription\Entities\SubstanceStockLog;
use Modules\Prescription\Entities\PrescriptionContainer;
use Modules\Prescription\Entities\PrescriptionSubstance;
use DateTime;

class PrescriptionController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->product_path = "img/product/";
    }

    public function list(Request $request):mixed
    {
        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescriptions = Prescription::with([
            'prescription_outlets' => function($prescription_outlets) use ($outlet){
                $prescription_outlets->where('outlet_id',$outlet['id']);
            }, 'category'
        ])->whereHas('prescription_outlets', function($prescription_outlets) use ($outlet){
            $prescription_outlets->where('outlet_id',$outlet['id']);
        });

        if(isset($post['search']) && !empty($post['search'])){
            $prescriptions = $prescriptions->where('prescription_name', 'like', '%'.$post['search'].'%');
        }

        $prescriptions = $prescriptions->original()->get()->toArray();

        $prescriptions = array_map(function($value){

            if(isset($value['prescription_outlets'][0]['price']) ?? false){
                $price = $value['prescription_outlets'][0]['price'] ?? 0;
            }else{
                $price = $value['price'] ?? 0;
            }
            $data = [
                'id'                => $value['id'],
                'prescription_name' => $value['prescription_name'],
                'type'              => $value['category']['category_name'] ?? '',
                'unit'              => $value['unit'],
                'price'             => $price,
                'stock'             => $value['prescription_outlets'][0]['stock'] ?? 0
            ];
            return $data;
        },$prescriptions ?? []);

        return $this->ok('success', $prescriptions);
    }

    public function addLogPrescriptionStockLog($id, $qty, $stock_before, $stock_after, $source, $desc){

        $stock_log = PrescriptionOutletLog::create([
            'prescription_outlet_id'  => $id,
            'qty'                     => $qty,
            'stock_before'            => $stock_before,
            'stock_after'             => $stock_after,
            'source'                  => $source,
            'description'             => $desc ?? null,
        ]);
    }

    public function addLogContainerStockLog($id, $qty, $stock_before, $stock_after, $source, $desc){

        $stock_log = ContainerStockLog::create([
            'container_stock_id'      => $id,
            'qty'                     => $qty,
            'stock_before'            => $stock_before,
            'stock_after'             => $stock_after,
            'source'                  => $source,
            'description'             => $desc ?? null,
        ]);
    }

    public function addLogSubstanceStockLog($id, $qty, $stock_before, $stock_after, $source, $desc){

        $stock_log = SubstanceStockLog::create([
            'substance_stock_id'      => $id,
            'qty'                     => $qty,
            'stock_before'            => $stock_before,
            'stock_after'             => $stock_after,
            'source'                  => $source,
            'description'             => $desc ?? null,
        ]);
    }

    public function categoriesCustom(Request $request):JsonResponse
    {
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescriptionCategories = PrescriptionCategory::select('id','category_name')->get()->toArray();
        if(!$prescriptionCategories){
            return $this->error('Category not found');
        }
        return $this->ok('success', $prescriptionCategories);
    }

    public function createCustom(Request $request):mixed
    {
        $request->validate([
            'id_order' => 'required',
            'custom_name' => 'required',
            'id_category' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescriptionCategories = PrescriptionCategory::where('id', $post['id_category'])->first();
        if(!$prescriptionCategories){
            return $this->error('Category not found');
        }

        $order = Order::where('id', $post['id_order'])->first();
        if(!$order){
            return $this->error('Order not found');
        }

        $last_code = Prescription::latest('prescription_code')->first()['prescription_code']??'';
        $last_code = $last_code == '' ? 0 : explode('-',$last_code)[1];
        $last_code = (int)$last_code + 1;
        $pres_code = 'PRE-'.sprintf("%02d", $last_code);
        $create = Prescription::create([
            'prescription_code' => $pres_code,
            'prescription_name' => $post['custom_name'],
            'prescription_category_id' => $prescriptionCategories['id'],
            'is_custom' => 1,
            'patient_id' => $order['patient_id'],
            'is_active' => 0,
        ]);

        if(!$create){
            return $this->error('Create prescription failed');
        }

        return $this->ok('success', $create);

    }

    public function listContainer(Request $request): mixed
    {
        $request->validate([
            'id_custom' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescription = Prescription::where('id', $post['id_custom'])->where('is_custom', 1)->first();
        if(!$prescription){
            return $this->error('Prescription not found');
        }

        $containers = Container::whereHas('categories', function($categories) use($prescription){
            $categories->where('prescription_category_id', $prescription['prescription_category_id']);
        })->get()->toArray();

        $containers = array_map(function($value){
            $value = [
                'id'   => $value['id'],
                'name' => $value['container_name'].' '.$value['type'].' - '.$value['unit'],
            ];
            return $value;
        }, $containers ?? []);

        return $this->ok('success', $containers);
    }

    public function listSubstance(Request $request): mixed
    {
        $request->validate([
            'id_custom' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescription = Prescription::where('id', $post['id_custom'])->where('is_custom', 1)->first();
        if(!$prescription){
            return $this->error('Prescription not found');
        }

        $limit = $post['limit'] ?? 10;

        $substances = Substance::with([
            'outlet_price' => function($outlet_price) use ($outlet){
                $outlet_price->where('outlet_id',$outlet['id']);
            }
        ])->whereHas('categories', function($categories) use($prescription){
            $categories->where('prescription_category_id', $prescription['prescription_category_id']);
        });

        if(isset($post['search_name']) ?? false){
            $substances = $substances->where('substance_name', 'like', '%'.$post['search_name'].'%');
        }

        $substances = $substances->select('id', 'substance_name', 'type', 'unit', 'price')
        ->paginate($post['limit'] ?? 10)->toArray();

        $data = [];
        foreach($substances['data'] ?? [] as $sub){
            $data[] = [
                'id' => $sub['id'],
                'substance_name' =>  $sub['substance_name'],
                'type' =>  $sub['type'],
                'unit' =>  $sub['unit'],
                'price' => ($sub['outlet_price'][0]['price'] ?? $sub['price']) ?? 0,
            ];
        }

        $substances['data'] = $data;

        return $this->ok('success', $substances);
    }

    public function getCustom(Request $request): mixed
    {
        $request->validate([
            'id_custom' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_custom'])){

            return $this->getDataCustom(true, ['prescription_id' => $post['id_custom'], 'outlet' => $outlet],'');
        }

        return $this->ok('', $return);
    }

    public function getDataCustom($status = true, $data, $message): mixed
    {
        $id_prescription = $data['prescription_id'];
        $outlet = $data['outlet'];

        $return = [
            'summary' => [
                [
                    'label' => 'Subtotal',
                    'value' => 0
                ]
            ],
        ];

        $prescription = Prescription::with([
            'category',
            'prescription_container.container.outlet_price' => function($outlet_price) use ($outlet){
                $outlet_price->where('outlet_id',$outlet['id']);
            },
            'prescription_substances.substance.outlet_price' => function($outlet_price) use ($outlet){
                $outlet_price->where('outlet_id',$outlet['id']);
            },
        ])->where('id', $id_prescription)
        ->whereHas('category')
        ->latest()->first();

        $price_total = 0;
        if($prescription){

            $container = [];
            $substances = [];

            if($prescription['prescription_container'] ?? false){

                $price = ($prescription['prescription_container']['container']['outlet_price'][0]['price'] ?? $prescription['prescription_container']['container']['price']) ?? 0;

                $container[] = [
                    'prescription_container_id' => $prescription['prescription_container']['id'],
                    'type' => 'Container',
                    'container_name' => $prescription['prescription_container']['container']['container_name'].' '.$prescription['prescription_container']['container']['type'],
                    'size' => $prescription['prescription_container']['container']['unit'],
                    'price' => $price
                ];

                $price_total += $price;
            }

            foreach($prescription['prescription_substances'] ?? [] as $sub){

                $price = (($sub['substance']['outlet_price'][0]['price'] ?? $sub['substance']['price']) ?? 0) * $sub['qty'];

                $substances[] = [
                    'prescription_substance_id' => $sub['id'],
                    'substance_name' => $sub['substance']['substance_name'],
                    'unit' => $sub['substance']['type'].' '.$sub['substance']['unit'],
                    'qty' => $sub['qty'],
                    'price' => $price
                ];

                $price_total += $price;

            }

            $return = [
                'prescription_id'   => $prescription['id'],
                'prescription_code' => $prescription['prescription_code'],
                'prescription_name' => $prescription['prescription_name'],
                'category'          => $prescription['category']['category_name'],
                'container'         => $container,
                'substances'        => $substances,
                'summary'           => [
                    [
                        'label' => 'Subtotal',
                        'value' => $price_total
                    ]
                ],
            ];
        }

        return $this->ok($message, $return);

    }

    public function addCustom(Request $request):mixed
    {

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_custom'])){

            $prescription = Prescription::where('id', $post['id_custom'])
            ->where('is_custom', 1)
            ->latest()
            ->first();

            DB::beginTransaction();
            if(!$prescription){
                return $this->error('Prescription not found');
            }

            if(($post['type']??false) == 'container'){

                if(isset($post['order']['id']) ?? false){
                    $container = Container::whereHas('categories', function($categories) use($prescription){
                        $categories->where('prescription_category_id', $prescription['prescription_category_id']);
                    })->where('id', $post['order']['id'])
                    ->first();

                    if(!$container){
                        DB::rollBack();
                        return $this->error('Container not found');
                    }

                    $prescription_container = PrescriptionContainer::where('prescription_id', $post['id_custom'])->first();
                    if($prescription_container){
                        $prescription_container->delete();
                    }

                    $prescription_container = PrescriptionContainer::create([
                        'prescription_id' => $post['id_custom'],
                        'container_id' => $container['id'],
                    ]);

                    if(!$prescription_container){
                        DB::rollBack();
                        return $this->error('Failed to create prescription container');
                    }

                    DB::commit();
                    return $this->getDataCustom(true, ['prescription_id' => $post['id_custom'], 'outlet' => $outlet],'Succes to add container');

                }else{
                    DB::rollBack();
                    return $this->error('ID is invalid');
                }

            }elseif(($post['type']??false) == 'substance'){

                if(isset($post['order']['id']) ?? false && isset($post['order']['qty']) ?? false){
                    $substance = Substance::whereHas('categories', function($categories) use($prescription){
                        $categories->where('prescription_category_id', $prescription['prescription_category_id']);
                    })->where('id', $post['order']['id'])
                    ->first();

                    if(!$substance){
                        DB::rollBack();
                        return $this->error('Container not found');
                    }

                    $prescription_substance = PrescriptionSubstance::create([
                        'prescription_id' => $post['id_custom'],
                        'substance_id' => $substance['id'],
                        'qty' => $post['order']['qty'],
                    ]);

                    if(!$prescription_substance){
                        DB::rollBack();
                        return $this->error('Failed to create prescription substance');
                    }

                    DB::commit();
                    return $this->getDataCustom(true, ['prescription_id' => $post['id_custom'], 'outlet' => $outlet],'Succes to add substance');

                }else{
                    DB::rollBack();
                    return $this->error('ID or QYT is invalid');
                }

            }else{
                DB::rollBack();
                return $this->error('Type is invalid');
            }

        }else{
            return $this->error('Prescription cant be null');
        }
    }

    public function submitCustom(Request $request):mixed
    {

        $request->validate([
            'id_custom' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescription = Prescription::where('id', $post['id_custom'])
        ->where('is_custom', 1)
        ->where('is_active', 0)
        ->whereHas('prescription_container')
        ->whereHas('prescription_substances')
        ->first();

        if(!$prescription){
            return $this->error('Prescription not found');
        }

        DB::beginTransaction();

        $update = $prescription->update([
            'is_active' => 1,
            'active_date' => date('Y-m-d')
        ]);

        if(!$update){
            DB::rollBack();
            return $this->error('Failed to submit prescription');
        }

        DB::commit();
        return $this->getDataCustom(true, ['prescription_id' => $post['id_custom'], 'outlet' => $outlet],'Succes to submit prescription');
    }

    public function listCustom(Request $request):mixed
    {
        $request->validate([
            'id_order' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $order = Order::where('id', $post['id_order'])
        ->where('send_to_transaction', 0)
        ->where('is_submited', 1)
        ->latest()
        ->first();

        if(!$order){
            return $this->error('Order not found');
        }

        $prescriptions = Prescription::with([
            'prescription_container.container.stocks' => function($container) use ($outlet){
                $container->where('outlet_id', $outlet['id']);
            },
            'prescription_container.container.outlet_price' => function($container) use ($outlet){
                $container->where('outlet_id', $outlet['id']);
            },
            'prescription_substances.substance.stocks' => function($substance) use ($outlet){
                $substance->where('outlet_id', $outlet['id']);
            },
            'prescription_substances.substance.outlet_price' => function($substance) use ($outlet){
                $substance->where('outlet_id', $outlet['id']);
            },
            'category'
        ])->where('patient_id', $order['patient_id'])
        ->where('is_active', 1)
        ->whereHas('prescription_container')
        ->whereHas('prescription_substances')
        ->whereHas('category')
        ->custom()
        ->orderBy('created_at', 'desc')
        ->get()->toArray();

        $data = [];
        $late = '';
        foreach($prescriptions ?? [] as $key_pre => $prescription){
            $price = 0;
            $price_total = 0;
            $qty_container = 0;
            $qty_substance = 0;
            $stock = 0;

            if($prescription['prescription_container'] ?? false){

                $price = ($prescription['prescription_container']['container']['outlet_price'][0]['price'] ?? $prescription['prescription_container']['container']['price']) ?? 0;
                $price_total += $price;

                $qty_container = $prescription['prescription_container']['container']['stocks'][0]['qty'] ?? 0;

            }

            foreach($prescription['prescription_substances'] ?? [] as $key_sub => $sub){

                $price = (($sub['substance']['outlet_price'][0]['price'] ?? $sub['substance']['price']) ?? 0) * $sub['qty'];
                $price_total += $price;

                $this_qty_subs = floor(($sub['substance']['stocks'][0]['qty'] ?? 0) / $sub['qty']);
                if($key_sub == 0){
                    $qty_substance = $this_qty_subs;
                }else{
                    if($qty_substance > $this_qty_subs){
                        $qty_substance = $this_qty_subs;
                    }
                }

            }

            if($qty_container > $qty_substance){
                $stock = $qty_substance;
            }elseif($qty_container < $qty_substance){
                $stock = $qty_container;
            }elseif($qty_container == $qty_substance){
                $stock = $qty_container;
            }else{
                $stock = 0;
            }

            $data[] = [
                'id' => $prescription['id'],
                'prescription_name' => $prescription['prescription_name'],
                'category_name' => $prescription['category']['category_name'],
                'price' => $price_total,
                'stock' => $stock,
            ];

            if($key_pre == 0){
                $late = new DateTime($prescription['active_date']);
                $now = new DateTime();
                $interval_d = $now->diff($late)->d;
                $interval_m = $now->diff($late)->m;
                $interval_y = $now->diff($late)->y;
                // return [$interval_d , $interval_m , $interval_y];
                if($interval_d == 0 && $interval_m == 0 && $interval_y == 0){
                    $last_created = 'A Moment Ago';
                }elseif($interval_d >= 1 && $interval_m == 0 && $interval_y == 0){
                    $last_created = $interval_d.' Days';
                }elseif($interval_m >= 1 && $interval_y == 0){
                    $last_created = $interval_m.' Months';
                }elseif($interval_y >= 1){
                    $last_created = $interval_y.' Years';
                }

            }

        }

        $return = [
            'last_created' => $last_created,
            'prescriptions' => $data
        ];

        return $this->ok('success', $return);
    }

}

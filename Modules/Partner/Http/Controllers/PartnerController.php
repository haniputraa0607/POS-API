<?php

namespace Modules\Partner\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Partner\Entities\PartnerEqual;
use Modules\Partner\Entities\PartnerSosialMedia;
use Modules\Partner\Entities\PartnerStore;

class PartnerController extends Controller
{
    public function webHookCreate(Request $request)
    {
        $post = $request->json()->all();
        $payload_partner_equal = [
            'equal_id' => $post['id'],
            'name' => $post['name'],
            'email' => $post['email'],
            'phone' => $post['phone'],
            'id_member' => $post['id_member'],
            'is_suspended' => $post['is_suspended'],
        ];
        $partner_equal = PartnerEqual::create($payload_partner_equal);
        $partner_equal_id = $partner_equal->id;
        $val_partner_store = PartnerStore::where(['equal_id' => $post['mitra_store']['id']])->first();
        if($val_partner_store){
            $partner_store = PartnerStore::find($val_partner_store->id)->update([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city'],
            ]);
            $partner_store_id = $val_partner_store->id;
        } else {
            $partner_store = PartnerStore::create([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city']
            ]);
            $partner_store_id = $partner_store->id;
        }
        foreach($post['mitra_store']['social_media_stores'] as $key){
            $val_sosial_media = PartnerSosialMedia::where([
                'equal_id' => $key['id'],
                'partner_store_id' => $partner_store_id
            ])->first();
            $type = '';
            switch($key['type']){
                case "1":
                    $type = "Instagram";
                    break;
                case "2":
                    $type = "Tiktok";
                    break;
                case "3":
                    $type = "Tokopedia";
                    break;
                case "4":
                    $type = "Shopee";
                    break;
                case "5":
                    $type = "Bukalapak";
                    break;
            }
            if($val_sosial_media){
                $partner_sosial_media = PartnerSosialMedia::find($val_sosial_media->id)->update([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url'] 
                ]);
            } else {
                $partner_sosial_media = PartnerSosialMedia::create([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url'] 
                ]);
            }
        }
        
        return $this->ok("succes", $post);
    } 

    public function webHookUpdate(Request $request)
    {
        $post = $request->json()->all();
        $payload_partner_equal = [
            'equal_id' => $post['id'],
            'name' => $post['name'],
            'email' => $post['email'],
            'phone' => $post['phone'],
            'id_member' => $post['id_member'],
            'is_suspended' => $post['is_suspended'],
        ];
        $val_partner_equal = PartnerEqual::where(['equal_id' => $post['id']])->first();
        $partner_equal = PartnerEqual::find($val_partner_equal->id)->update($payload_partner_equal);
        $partner_equal_id = $val_partner_equal->id;
        $val_partner_store = PartnerStore::where(['equal_id' => $post['mitra_store']['id']])->first();
        if($val_partner_store){
            $partner_store = PartnerStore::find($val_partner_store->id)->update([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city'],
            ]);
            $partner_store_id = $val_partner_store->id;
        } else {
            $partner_store = PartnerStore::create([
                'partner_equal_id' => $partner_equal_id,
                'equal_id' => $post['mitra_store']['id'],
                'store_name' => $post['mitra_store']['store_name'],
                'store_address' => $post['mitra_store']['store_address'],
                'store_city' => $post['mitra_store']['store_city']
            ]);
            $partner_store_id = $partner_store->id;
        }
        foreach($post['mitra_store']['social_media_stores'] as $key){
            $val_sosial_media = PartnerSosialMedia::where([
                'equal_id' => $key['id'],
                'partner_store_id' => $partner_store_id
            ])->first();
            $type = '';
            switch($key['type']){
                case "1":
                    $type = "Instagram";
                    break;
                case "2":
                    $type = "Tiktok";
                    break;
                case "3":
                    $type = "Tokopedia";
                    break;
                case "4":
                    $type = "Shopee";
                    break;
                case "5":
                    $type = "Bukalapak";
                    break;
            }
            if($val_sosial_media){
                $partner_sosial_media = PartnerSosialMedia::find($val_sosial_media->id)->update([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url'] 
                ]);
            } else {
                $partner_sosial_media = PartnerSosialMedia::create([
                    'equal_id' => $key['id'],
                    'partner_store_id' => $partner_store_id,
                    'type' => $type,
                    'url' => $key['url'] 
                ]);
            }
        }
        
        return $this->ok("succes", $post);
    }

    public function webHookDelete(Request $request)
    {   
        $post = $request->json()->all();
        $val_partner_store = PartnerStore::where(['equal_id' => $post['mitra_store']['id']])->first();
        if($val_partner_store){
            if($post['mitra_store']['social_media_stores']){
                foreach($post['mitra_store']['social_media_stores'] as $key){
                    $val_sosial_media = PartnerSosialMedia::where([
                        'equal_id' => $key['id'],
                        'partner_store_id' => $val_partner_store->id
                    ])->first();
                    if($val_sosial_media)
                        $deleted_sosial_media = PartnerSosialMedia::find($val_sosial_media->id)->delete();
                }
            }
        }
        if($val_partner_store)
            $deleted_store = PartnerStore::find($val_partner_store->id)->delete();
        $val_partner_equal = PartnerEqual::where(['equal_id' => $post['id']])->first();
        if($val_partner_equal)
            $partner = PartnerEqual::find($val_partner_equal->id)->delete();
        return $this->ok("success","");
    }
}

<?php

namespace App\Traits; 
use Illuminate\Http\Request;
use App\Models\Client\ManualOrders;
use App\Models\Client\Customers;

use Illuminate\Support\Facades\File; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

trait TraxTraits {
    
    use CurlTraits;
    // public function CurlGetRequest($url)
    // {
    //     $headers = ['Authorization:'.env('TRAX_API_KEY'), 'Accepts:' . 'application/json',"real:json content"];
    //     $ch = curl_init();
    //     curl_setopt($ch,CURLOPT_URL, $url);
    //     curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    //     $result = curl_exec($ch);
    //     curl_close($ch);
    //     return $result;
    // } 
    
    // public function CurlPostRequest($url,$data)
    // {
    //     $headers = ['Authorization:'.env('TRAX_API_KEY'), 'Accepts:' . 'application/json'];
    //     $ch = curl_init();
    //     curl_setopt($ch,CURLOPT_URL, $url);
    //     curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch,CURLOPT_POST, true);
    //     curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     $result = curl_exec($ch);
    //     return $result;
    // } 
    
    
    
    public function GetPickupAddresses()
    { 
        $apiUrl = "https://sonic.pk/api/pickup_addresses";
        $headers = ['Authorization:'.env('TRAX_API_KEY'), 'Accepts:' . 'application/json',"real:json content"];
        $response = $this->CurlGetRequest($apiUrl,$headers);
        return $response = json_decode($response);
    }
    
    public function GetCities()
    { 
        $apiUrl = "https://sonic.pk/api/cities";
        $headers = ['Authorization:'.env('TRAX_API_KEY'), 'Accepts:' . 'application/json',"real:json content"];
        $response = $this->CurlGetRequest($apiUrl,$headers);
        return $response = json_decode($response);
    }
    
    
    public function CreateBooking($data)
    {
        $url = "https://sonic.pk/api/shipment/book";
        $headers = ['Authorization:'.env('TRAX_API_KEY'), 'Accepts:' . 'application/json',"real:json content"];
        $response = $this->CurlPostRequest($url,$headers,$data);
        return $response = json_decode($response);
    }
    
    
    public function PrintAirWayBill($tracking_number, $print_type)
    {
        //dd(asset('storage/file.txt'));
        $url = "https://sonic.pk/api/shipment/air_waybill?tracking_number=".$tracking_number.'&type='.$print_type;
        $headers = ['Authorization:'.env('TRAX_API_KEY'),'Content-Encoding: none','Content-Type: application/jpeg'];
        $timeout = 30;
        $response = $this->CurlGetRequest($url,$headers);
        $imageData = base64_encode($response);
        $src = 'data:image/png;base64,'.$imageData;
        return $src;
        //$path = '<img src="' . $src . '" >';
        //echo $path;
        //dd();
    }
    
    function base64_to_jpeg($base64_string, $output_file) {
        // open the output file for writing
        $ifp = fopen( $output_file, 'wb' ); 
    
        // split the string on commas
        // $data[ 0 ] == "data:image/png;base64"
        // $data[ 1 ] == <actual base64 string>
        $data = explode( ',', $base64_string );
    
        // we could add validation here with ensuring count( $data ) > 1
        fwrite( $ifp, base64_decode( $data[ 1 ] ) );
    
        // clean up the file resource
        fclose( $ifp ); 
    
        return $output_file; 
    }
    
    
    
}
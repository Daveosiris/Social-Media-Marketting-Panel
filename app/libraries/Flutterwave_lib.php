

<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Flutter Wave Library for CodeIgniter 3.X.X
 *
 * Library for Flutter Wave payment gateway. It helps to integrate Flutter Wave payment gateway's Standard Method
 * in the CodeIgniter application.
 *
 * It requires Flutterwave configuration file and it should be placed in the config directory.
 *
 * @package     CodeIgniter
 * @category    Libraries
 * @author      Jaydeep Goswami
 * @link        https://infinitietech.com
 * @GITHUB link https://github.com/jaydeepgiri/Flutterwave-Payments-CodeIgniter-3.X.X-Library
 * @license     https://github.com/jaydeepgiri/Flutterwave-Payments-CodeIgniter-3.X.X-Library/blob/master/LICENSE
 * @version     1.0
 */

class Flutterwave_lib{
    var $payment_url,$verify_url;
    var $PBFPubKey, $SECKEY, $txn_prefix;
    protected $currency = 'NGN';
    var $post_data = array();
    var $CI;

    function __construct(){
        $this->CI = & get_instance();
        $this->CI->load->helper('url');
        $this->CI->load->helper('form');
        $this->CI->load->database();
        //$this->CI->load->config('flutterwave');

        $this->CI->db->where('name', 'FlutterWave');
        $flutterwave = $this->CI->db->get('payments')->row();
        $array = json_decode($flutterwave->params);

        //$settings = $this->CI->db->get_where('settings', array('id' => 1))->row();

        $payment_endpoint = $array->option->environment != 'live' ? 'https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/hosted/pay' : 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay';
        $verify_endpoint = $array->option->environment != 'live' ? 'https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify' : 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify';

        $this->payment_url = $payment_endpoint;
        $this->verify_url = $verify_endpoint;
        $this->PBFPubKey = $array->option->public_key;
        $this->SECKEY = $array->option->secret_key;
        $this->currency = 'USD';
        $this->txn_prefix = 'VC_';
    }

    function create_payment($data){
        $data['PBFPubKey'] = $this->PBFPubKey;
        $data['currency'] = $this->currency;
        $data['txref'] = $this->txn_prefix . date('YmdHis');
        $response = $this->curl_post($this->payment_url, $data,TRUE);
        return $response;
    }

    function verify_transaction($reference){
        $data = array(
            "SECKEY" => $this->SECKEY,
            "txref" => $reference
        );
        $response = $this->curl_post($this->verify_url, $data,TRUE);
        return $response;
    }

    function curl_post($url, $data,$json_encode_data = FALSE){

        $data = ($json_encode_data)?json_encode($data):$data;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                "content-type: application/json",
                "cache-control: no-cache"
            ],
        ));
        $response = curl_exec($curl);

        if($err = curl_error($curl)){
            curl_close($curl);
            return "CURL Error : ".$err;
        }else{
            curl_close($curl);
            return $response;
        }
    }
}
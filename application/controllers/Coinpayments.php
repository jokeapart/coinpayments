<?php
defined('BASEPATH') or die('Direct access is not allowed');
class Coinpayments extends MY_Controller {
  public function __construct()
  {
    parent::__construct();
    //load your model
    $this->load->model('my_model');
  }
    public function depositCoin(){
        $this->load->helper('string');
        $this->Setup($private_key, $public_key);
        $email = $this->session->email; // Customers email
        $usd_amount = $this->input->post('amount', true); // USD amount customer is paying
        $coin_type = $this->input->post('payMethod', true); //The selected coin type whose address to be returned to customer for payment e.g BTC, ETH
        $unique_id = random_string('alnum', 12); // A unique identifier for referencing this transaction
          $req = array(
            'amount' => $amount,
            'currency1' => 'USD',
            'currency2' => $coin_type,
            'address' => '', // leave blank send to follow your settings on the Coin Settings page
            'buyer_email' => $email,
            'custom' => $unique_id,
            'ipn_url' => 'https://example.com/coinpayments/ipn_handler.php',// A webhook to receive updates for all transactions..more on this later
          );
        //call the CreateTransaction function to initiate payment     
        $result = $this->CreateTransaction($req);
        
        //Coinpayment returned result as json
        if ($result['error'] == 'ok') {
            $le = php_sapi_name() == 'cli' ? "\n" : '<br />';
            
            //Collect array values in variables
            print 'Transaction created with ID: '.$result['result']['txn_id'].$le;
            $address = $result['result']['address'];
            $trnxid = $result['result']['txn_id'];
            $transtatus = $result['result']['status_url'];
            $qrcode = $result['result']['qrcode_url'];
            $btc_amount = $result['result']['amount'];
            $timeout = $result['result']['timeout'];
           
            //Save this transaction into your database
            if($this->my_model->save_deposit($email, $usd_amount, $trnxid, $btc_amount,$unique_id,$coin_type)){
                    //save the values into session and pass into your view to display to payment instructions to customer
                    $session_transaction= array(
                        'trnxid' => $trnxid,
                        'qrcode' => $qrcode,
                        'btcamount' => $btc_amount,
                        'timeout' => $timeout,
                        'btcaddress' => $address,
                        'trxnid' => $trnxid,
                        'cointype' => $coin_type
                     );				
                  $this->session->set_userdata($session_transaction);
                  //redirect to a page where customer can view QRcode and other payment details
				          redirect(site_url('deposit_crypto'));
              }else{
                //if there is an error do something
               
              }
        } else {
            print 'Error: '.$result['error']."\n";
        } 
    }
    public function withdrawCoin(){
        $this->load->helper('string');
        $this->Setup($private_key, $public_key);
        $email = $this->session->email; // Customers email
        $usd_amount = $this->input->post('amount', true); //USD value to withdraw
        $coin_type = $this->input->post('payMethod', true); //The selected coin type whose address to be returned to customer for payment e.g BTC, ETH
        $unique_id = random_string('alnum', 12); // A unique identifier for referencing this transaction
        $btcAddress = $this->input->post('crypto_detail',true); //The customer address merchant wants to make payment to
        
          //You should do your check if customer has enough balance he/she has requested to withdraw befor this next step.
       
            $req = array(
              'amount' => $amount,	
              'currency' => $coinType,
              'address' => $btcAddress, 
              'auto_confirm' => 1,		
              'ipn_url' => 'https://example.com/coinpayments/ipn_handler.php',// A webhook to receive updates for all transactions..more on this later
             );
             
         //call the CreateWithdrawal function to initiate customer withdrawal  
         $result = $this->CreateWithdrawal($req);
         if ($result['error'] == 'ok') {
         
              //print 'Withdrawal created with ID: '.$result['result']['id'];
              $trnxid = $result['result']['id'];
              $status = $result['result']['status'];
              $btc_amount = $result['result']['amount'];
              
              if(($status==1)||($status==0)){
                //Withdrawal was successful
                //You might want to update customer balance here example subtract the $usd_amount from the customers old balance and
                //update your database correctly
                $newBalance = $userBalance - $withdrawal_amount;
                $balanceUpdate = $this->db->query("UPDATE balance SET balance='$newBalance' WHERE customer_id='$email'");
                if($balanceUpdate){
                //Udate your database
                $queriy=$this->my_model->save_withdrawal($email, $usd_amount, $trnxid, $btc_amount,$unique_id,$coin_type,$btcAddress);
              }
                  if($queriy){
                    echo 'success';
                  }else{
                    echo 'failed';
                  }
	                        
         }else {
            print 'Error: '.$result['error']."\n";
         }       
        
      }
} ?>

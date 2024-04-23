<?php
// namespace App\Mail;
namespace App\Http\Controllers\Auth;
namespace App\Http\Controllers;
use App\Mail\WelcomeUserEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use League\Csv\Writer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class DistributorSyndicationController extends Controller
{   
    use Queueable, SerializesModels;
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }
    
    public function syndicationHandler(Request $request)
    {
        $response = array();

        // User setup
        $user_id = '';
        $emailExists = User::where('email', $request['user']['email'])->first();
        $syndication_exist = $this->getSyndication($request['configurations']['distributor_id']);

        if($request['request_type'] == 'insert'){
            if(!is_null($emailExists)){
                $response['user'] = $emailExists->id;
                $response['status'] = 'User already exist!';
                return response()->json(['request' => $response]); 
            }

            if($syndication_exist != ''){
                $response['syndication'] = $syndication_exist;
                $response['status'] = 'Syndication with this distriutor already exist!';
                return response()->json(['request' => $response]); 
            }
        }

        if (!$emailExists && $syndication_exist == '') {
            // Insert the user into the database
            $user = new User();
            $user->name = $request['user']['name'];
            $user->email = $request['user']['email'];
            $user->phone_number = $request['user']['phone'];
            $user->password = bcrypt($request['user']['password']);
            $user->save();
            $user_id = $user->id;
            $response['user'] = 'User created!';
            // $this->notifyNewUserEmail();
        }else{
            $user_id = $emailExists->id;
            $response['user'] = 'User already exist!'.$user_id;
            
        }

        // Configuration setup
        $config_id='';
        if($syndication_exist == '' && $user_id != ''){
            $config_id = DB::table('distributor_syndication')->insertGetId([
                'user_id' => $user_id,
                'distributer_id' => $request['configurations']['distributor_id'],
                'distributor_name' => $request['configurations']['distributor_name'],
                'mailing_address' => $request['configurations']['mailing_address'],
                'state' => $request['configurations']['state'],
                'city' => $request['configurations']['city'],
                'zip_code' => $request['configurations']['zip_code'],
                'distributor_account' => $request['configurations']['distributor_account'],
                'syndication_type' => $request['configurations']['syndication_type'],
                'csv_link' => '',
                'last_job_run_time' => '',
                'to_run' => '',
                'needs_update' => '',
                'is_synced' => '',
                'remember_token' => ''
            ]);
            $response['syndication'] = 'Syndication created!';
        }else{
            $config_id = $syndication_exist;
            $response['syndication'] = 'Syndication already exist!'.$syndication_exist;
        }

        // Products
        if($user_id != '' && $config_id != ''){
            // Checking if syndication products already exist
            $config_exist = $this->getSyndicationProducts($request['configurations']['distributor_id']);
            if(!is_null($config_exist)){
                $this->deleteUserSyndicationProducts($request['configurations']['distributor_id']); // If exist then delete 
            }

            // Adding new syndication
            foreach($request['products'] as $prod){
                DB::table('distributor_syndication_configurations')->insertGetId([
                    'synd_id' => $config_id,
                    'distributer_id' => $request['configurations']['distributor_id'],
                    'prod_id' => $prod['id'],
                    'product_type' => $prod['type']
                ]);
            }
        }

        if($request['request_type'] == 'insert')
        {
            $response['status'] = 'Syndication setup successfully!';
        }else{
            $response['status'] = 'Syndication updated successfully!';
        }
        // Return a success response
        return response()->json(['request' => $response]);
    }

    public function getSyndication($distributor_id){
        $syndication = DB::table('distributor_syndication')->where('distributer_id', $distributor_id)->first();
        if ($syndication) {
            return $syndication->synd_id;
        }
        return '';
    }

    public function getSyndicationProducts($distributor_id){
        $syndication = DB::table('distributor_syndication_configurations')->where('distributer_id', $distributor_id)->first();
        if ($syndication) {
            return $syndication->id;
        }
        return '';
    }

    public function deleteUserSyndicationProducts($distributer_id){
        DB::table('distributor_syndication_configurations')->where('distributer_id', $distributer_id)->delete();
    }

    public function getDistributorSyndication(Request $request){
        $data = array();
        $user = '';
        $rows = '';
        $contracted_products = array();
        $standard_products = array();
        $distributor_id = $request['distributor_id'];
        $syndication = DB::table('distributor_syndication')->where('distributer_id', $distributor_id)->first(); // Syndication
        if(!is_null($syndication)){
            $user = DB::table('users')->where('id', $syndication->user_id)->first(); // User
            $rows = DB::table('distributor_syndication_configurations')->where('distributer_id', $distributor_id)->get(); // Getting products
        }
        // Identifying products
        if(!empty($rows) && !is_null($rows)){
            foreach($rows as $product){
                if($product->product_type == 'standard'){
                $standard_products[] = $product->prod_id;
                } 
                if($product->product_type == 'contracted'){
                $contracted_products[] = $product->prod_id;
                }
            }
        }
        $data['syndication'] = $syndication;
        $data['user'] = $user;
        $data['standard_products'] = $standard_products;
        $data['contracted_products'] = $contracted_products;
        $data['standard_classes'] = '';
        return response()->json(['response' => $data]);
    }

    public function getCurrentUserSyndication()
    {
        $user_id = Auth::id();
        $data = DB::table('distributor_syndication')->where('user_id', $user_id)->first();
        return $data;
    }

    public function getCurrentUuserProducts($synd_id){
        $data = array(
            'standard' => array(),
            'contracted' => array()
        );
        $products = DB::table('distributor_syndication_configurations')->where('synd_id', $synd_id)->get();
        if ($products) {
            foreach($products as $val){
                $product = DB::table('tbl_product_information')->where('product_id', $val->prod_id)->select('sku', 'name', 'category', 'unit')->get();
                foreach($product as $prod){
                    if($val->product_type == 'standard'){
                        $data['standard'][] = $prod;
                    }else{
                        $data['contracted'][] = $prod;
                    }
                }
            }
        }
        return $data;
    }

    public function getPIMProduct($pid){
        $product = DB::connection('PIMDB')->table('pws_pods_product')->where('id', $pid)->select('sku', 'product_name', 'product_category', 'h_ship', 'bom_unit', 'sub_brand_category')->get();
        
        return $data = array(
            'product_info' => $product['0']
        );
    }

    public function getPIMProductPackaging($pid){
        $product = DB::connection('PIMDB')->table('pws_pods_product_packaging')->where('id', $pid)->select('sku', 'product_name', 'product_category', 'h_ship', 'bom_unit', 'sub_brand_category')->get();
        return $data = array(
            'product_info' => $product['0']
        );
    }

    public function addPIMProduct(Request $request){
        $response = array();
        $row_id = DB::table('tbl_product_information')->insertGetId([
            'product_id' => $request['product_info']['pid'],
            'sku' => $request['product_info']['sku'],
            'name' => $request['product_info']['name'],
            'category' => $request['product_info']['category'],
            'h_ship' => $request['product_info']['ship'],
            'unit' => $request['product_info']['unit'],
            'material' => $request['product_info']['material'],
            'material_es' => $request['product_info']['material_es'],
            'hose_diameter' => $request['product_packaging']['hose_diameter'],
            'length' => $request['product_packaging']['length'],
            'carton_size' => $request['product_packaging']['carton_size'],
            'carton_weight' => $request['product_packaging']['carton_weight'],
            'ship_via' => $request['product_packaging']['ship_via'],
            'marketing_desc_en' => $request['description_en']['description'],
            'feature_1_en' => $request['description_en']['feature_1'],
            'feature_2_en' => $request['description_en']['feature_2'],
            'feature_3_en' => $request['description_en']['feature_3'],
            'feature_4_en' => $request['description_en']['feature_4'],
            'feature_5_en' => $request['description_en']['feature_5'],
            'feature_6_en' => $request['description_en']['feature_6'],
            'feature_7_en' => $request['description_en']['feature_7'],
            'feature_8_en' => $request['description_en']['feature_8'],
            'feature_9_en' => $request['description_en']['feature_9'],
            'feature_10_en' => $request['description_en']['feature_10'],
            'feature_11_en' => $request['description_en']['feature_11'],
            'feature_12_en' => $request['description_en']['feature_12'],
            'feature_13_en' => $request['description_en']['feature_13'],
            'marketing_desc_es' => $request['description_es']['description'],
            'feature_1_es' => $request['description_es']['feature_1'],
            'feature_2_es' => $request['description_es']['feature_2'],
            'feature_3_es' => $request['description_es']['feature_3'],
            'feature_4_es' => $request['description_es']['feature_4'],
            'feature_5_es' => $request['description_es']['feature_5'],
            'feature_6_es' => $request['description_es']['feature_6'],
            'feature_7_es' => $request['description_es']['feature_7'],
            'feature_8_es' => $request['description_es']['feature_8'],
            'feature_9_es' => $request['description_es']['feature_9'],
            'feature_10_es' => $request['description_es']['feature_10'],
            'feature_11_es' => $request['description_es']['feature_11'],
            'feature_12_es' => $request['description_es']['feature_12'],
            'feature_13_es' => $request['description_es']['feature_13'],
            'marketing_app_1' => $request['marketing_applications']['market_application_1'],
            'marketing_app_2' => $request['marketing_applications']['market_application_2'],
            'marketing_app_3' => $request['marketing_applications']['market_application_3'],
            'marketing_app_4' => $request['marketing_applications']['market_application_4'],
            'marketing_app_5' => $request['marketing_applications']['market_application_5'],
            'marketing_app_6' => $request['marketing_applications']['market_application_6'],
            'marketing_app_7' => $request['marketing_applications']['market_application_7'],
            'marketing_app_8' => $request['marketing_applications']['market_application_8'],
            'marketing_app_9' => $request['marketing_applications']['market_application_9'],
            'marketing_app_10' => $request['marketing_applications']['market_application_10'],
            'marketing_app_11' => $request['marketing_applications']['market_application_11'],
            'marketing_app_12' => $request['marketing_applications']['market_application_12'],
            'marketing_app_13' => $request['marketing_applications']['market_application_13'],
            'marketing_app_14' => $request['marketing_applications']['market_application_14'],
            'marketing_app_15' => $request['marketing_applications']['market_application_15'],
            'marketing_app_16' => $request['marketing_applications']['market_application_16'],
            'marketing_app_17' => $request['marketing_applications']['market_application_17'],
            'marketing_app_18' => $request['marketing_applications']['market_application_18'],
            'marketing_app_19' => $request['marketing_applications']['market_application_19'],
            'marketing_app_20' => $request['marketing_applications']['market_application_20'],
            'marketing_app_21' => $request['marketing_applications']['market_application_21'],
            'marketing_app_22' => $request['marketing_applications']['market_application_22'],
            'marketing_app_23' => $request['marketing_applications']['market_application_23'],
            'certification' => $request['certs_warns']['certification'],
            'ul_listed_as_94v' => $request['certs_warns']['ul_94v_0'],
            'prop_65_warn' => $request['certs_warns']['prop_65_warning'],
            'reach' => $request['certs_warns']['reach'],
            'nafta' => $request['certs_warns']['naphtha'],
            'rohs' => $request['certs_warns']['rohs'],
            'a' => $request['product_ratings']['a'],
            'f' => $request['product_ratings']['f'],
            'd' => $request['product_ratings']['d'],
            'm' => $request['product_ratings']['m'],
            'ru' => $request['product_ratings']['ru'],
            'class_insstringe_diameter' => $request['class_specs']['inside_diameter'],
            'class_temprature' => $request['class_specs']['temprature'],
            'class_length' => $request['class_specs']['length'],
            'datasheet' => $request['product_documents']['datasheet'],
            'datasheet_es' => $request['product_documents']['datasheet_es'],
            'customer_provstringed_doc' => $request['product_documents']['customer_provided_documents'],
            'media_other' => $request['media']['others'],
            'media_photo1' => $request['media']['photo_1'],
            'media_photo2' => $request['media']['photo_2'],
            'media_photo3' => $request['media']['photo_3'],
            'media_photo4' => $request['media']['photo_4'],
            'media_cuffdrawing' => $request['media']['cuff_drawing'],
            'media_customerdrawing1' => $request['media']['customer_drawing_1'],
            'media_customerdrawing2' => $request['media']['customer_drawing_2'],
            'media_profiledrawing' => $request['media']['profile_drawing'],
        ]);
        // Check if the result is true
        if ($row_id) {
            $response['msg'] = "Product added successfully!";
            $response['pid'] = $row_id;
        } else {
            $response['msg'] = "Query failed!";
        }
        // $response[] = $request['product_info']['pid'];
        return response()->json(['request' => $response]);
    }

    public function getProductRowID($product_id){

        $product = DB::table('tbl_product_information')->where('product_id', $product_id)->first();
        if ($product) {
            return $product->id;
        }
    }

    public function updateProductInformation(Request $request){
        $pid = $request['product_info']['pid'];
        $row_id = $this->getProductRowID($pid);
        $response = array();
        // Update query
        if($row_id){
            $affectedRows  = DB::table('tbl_product_information')
            ->where('id', $row_id) // Assuming $id is the ID of the record you want to update
            ->update([
                'product_id' => $request['product_info']['pid'],
                'name' => $request['product_info']['name'],
                'category' => $request['product_info']['category'],
                'h_ship' => $request['product_info']['ship'],
                'unit' => $request['product_info']['unit'],
                'material' => $request['product_info']['material'],
                'material_es' => $request['product_info']['material_es'],
                'hose_diameter' => $request['product_packaging']['hose_diameter'],
                'length' => $request['product_packaging']['length'],
                'carton_size' => $request['product_packaging']['carton_size'],
                'carton_weight' => $request['product_packaging']['carton_weight'],
                'ship_via' => $request['product_packaging']['ship_via'],
                'marketing_desc_en' => $request['description_en']['description'],
                'feature_1_en' => $request['description_en']['feature_1'],
                'feature_2_en' => $request['description_en']['feature_2'],
                'feature_3_en' => $request['description_en']['feature_3'],
                'feature_4_en' => $request['description_en']['feature_4'],
                'feature_5_en' => $request['description_en']['feature_5'],
                'feature_6_en' => $request['description_en']['feature_6'],
                'feature_7_en' => $request['description_en']['feature_7'],
                'feature_8_en' => $request['description_en']['feature_8'],
                'feature_9_en' => $request['description_en']['feature_9'],
                'feature_10_en' => $request['description_en']['feature_10'],
                'feature_11_en' => $request['description_en']['feature_11'],
                'feature_12_en' => $request['description_en']['feature_12'],
                'feature_13_en' => $request['description_en']['feature_13'],
                'marketing_desc_es' => $request['description_es']['description'],
                'feature_1_es' => $request['description_es']['feature_1'],
                'feature_2_es' => $request['description_es']['feature_2'],
                'feature_3_es' => $request['description_es']['feature_3'],
                'feature_4_es' => $request['description_es']['feature_4'],
                'feature_5_es' => $request['description_es']['feature_5'],
                'feature_6_es' => $request['description_es']['feature_6'],
                'feature_7_es' => $request['description_es']['feature_7'],
                'feature_8_es' => $request['description_es']['feature_8'],
                'feature_9_es' => $request['description_es']['feature_9'],
                'feature_10_es' => $request['description_es']['feature_10'],
                'feature_11_es' => $request['description_es']['feature_11'],
                'feature_12_es' => $request['description_es']['feature_12'],
                'feature_13_es' => $request['description_es']['feature_13'],
                'marketing_app_1' => $request['marketing_applications']['market_application_1'],
                'marketing_app_2' => $request['marketing_applications']['market_application_2'],
                'marketing_app_3' => $request['marketing_applications']['market_application_3'],
                'marketing_app_4' => $request['marketing_applications']['market_application_4'],
                'marketing_app_5' => $request['marketing_applications']['market_application_5'],
                'marketing_app_6' => $request['marketing_applications']['market_application_6'],
                'marketing_app_7' => $request['marketing_applications']['market_application_7'],
                'marketing_app_8' => $request['marketing_applications']['market_application_8'],
                'marketing_app_9' => $request['marketing_applications']['market_application_9'],
                'marketing_app_10' => $request['marketing_applications']['market_application_10'],
                'marketing_app_11' => $request['marketing_applications']['market_application_11'],
                'marketing_app_12' => $request['marketing_applications']['market_application_12'],
                'marketing_app_13' => $request['marketing_applications']['market_application_13'],
                'marketing_app_14' => $request['marketing_applications']['market_application_14'],
                'marketing_app_15' => $request['marketing_applications']['market_application_15'],
                'marketing_app_16' => $request['marketing_applications']['market_application_16'],
                'marketing_app_17' => $request['marketing_applications']['market_application_17'],
                'marketing_app_18' => $request['marketing_applications']['market_application_18'],
                'marketing_app_19' => $request['marketing_applications']['market_application_19'],
                'marketing_app_20' => $request['marketing_applications']['market_application_20'],
                'marketing_app_21' => $request['marketing_applications']['market_application_21'],
                'marketing_app_22' => $request['marketing_applications']['market_application_22'],
                'marketing_app_23' => $request['marketing_applications']['market_application_23'],
                'certification' => $request['certs_warns']['certification'],
                'ul_listed_as_94v' => $request['certs_warns']['ul_94v_0'],
                'prop_65_warn' => $request['certs_warns']['prop_65_warning'],
                'reach' => $request['certs_warns']['reach'],
                'nafta' => $request['certs_warns']['naphtha'],
                'rohs' => $request['certs_warns']['rohs'],
                'a' => $request['product_ratings']['a'],
                'f' => $request['product_ratings']['f'],
                'd' => $request['product_ratings']['d'],
                'm' => $request['product_ratings']['m'],
                'ru' => $request['product_ratings']['ru'],
                'class_insstringe_diameter' => $request['class_specs']['inside_diameter'],
                'class_temprature' => $request['class_specs']['temprature'],
                'class_length' => $request['class_specs']['length'],
                'datasheet' => $request['product_documents']['datasheet'],
                'datasheet_es' => $request['product_documents']['datasheet_es'],
                'customer_provstringed_doc' => $request['product_documents']['customer_provided_documents'],
                'media_other' => $request['media']['others'],
                'media_photo1' => $request['media']['photo_1'],
                'media_photo2' => $request['media']['photo_2'],
                'media_photo3' => $request['media']['photo_3'],
                'media_photo4' => $request['media']['photo_4'],
                'media_cuffdrawing' => $request['media']['cuff_drawing'],
                'media_customerdrawing1' => $request['media']['customer_drawing_1'],
                'media_customerdrawing2' => $request['media']['customer_drawing_2'],
                'media_profiledrawing' => $request['media']['profile_drawing'],
                'is_updated' => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }else{
            $response = array(
                'msg' => "No data found against this id.",
            );
        }
        if ($affectedRows > 0) {
            // Update successful
            $response = array(
                'msg' => 'Product updated!',
                'pid' => $pid
            );
        } else {
            // Update failed
            $response = array(
                'msg' => "Update failed. No rows affected.",
            );
        }
        return response()->json(['request' => $response]);
    }

    public function getCurrentUserProducts(){
        $products = array();
        $syndication = $this->getCurrentUserSyndication();
        $rows = DB::table('distributor_syndication_configurations')->where('synd_id', $syndication->synd_id)->get();
        foreach($rows as $val){
            $product = DB::table('tbl_product_information')->where('product_id', $val->prod_id)->get();
            foreach($product as $val){
                $products[] = $val;
            }
        }
        return $products;
    }

    public function generatecsv()
    {
        $products = $this->getCurrentUserProducts();
        // echo "<pre>"; print_r($products);
        $csv = Writer::createFromFileObject(new \SplTempFileObject());

        // Add headers
        $headers =  array(
            'SKU',
            'Name',
            'Category',
            '24H ship',
            'Unit',
            'Material',
            'Material ES',
            'Hose diameter',
            'Length',            
            'Carton size',               
            'Carton weight',
            'Ship via',              
            'Marketing description EN',
            'Feture 1 en',
            'Feture 2 en',
            'Feture 3 en',
            'Feture 4 en',
            'Feture 5 en',
            'Feture 6 en',
            'Feture 7 en',
            'Feture 8 en',
            'Feture 9 en',
            'Feature 10 en',
            'Feature 11 en',
            'Feature 12 en',
            'Feature 13 en',
            'Marketing description ES',
            'Feature 1 es',
            'Feature 2 es',
            'Feature 3 es',
            'Feature 4 es',
            'Feature 5 es',
            'Feature 6 es',
            'Feature 7 es',
            'Feature 8 es',
            'Feature 9 es',
            'Feature 10 es',
            'Feature 11 es',
            'Feature 12 es',
            'Feature 13 es',
            'Marketing app 1',
            'Marketing app 2',
            'Marketing app 3',
            'Marketing app 4',
            'Marketing app 5',
            'Marketing app 6',
            'Marketing app 7',
            'Marketing app 8',
            'Marketing app 9',
            'Marketing app 10',
            'Marketing app 11',
            'Marketing app 12',
            'Marketing app 13',
            'Marketing app 14',
            'Marketing app 15',
            'Marketing app 16',
            'Marketing app 17',
            'Marketing app 18',
            'Marketing app 19',
            'Marketing app 20',
            'Marketing app 21',
            'Marketing app 22',
            'Marketing app 23',
            'Certification',
            'Ul listed as 94v',
            'Prop 65 warn',
            'Reach',
            'Nafta',
            'Rohs',
            'A',
            'F',
            'D',
            'M',
            'RU',
            'Class insstringe diameter',
            'Class temprature',
            'Class length',
            'Datasheet',
            'Datasheet es',
            'Customer provstringed doc',
            'Media other',
            'Media photo1',
            'Media photo2',
            'Media photo3',
            'Media photo4',
            'Media cuffdrawing',
            'Media customerdrawing1',
            'Media customerdrawing2',
            'Media profiledrawing'
        );
        $csv->insertOne($headers);

        foreach ($products as $row) {

            $d = array(
                $row->sku,
                $row->name,
                $row->category,
                $row->h_ship,
                $row->unit,
                $row->material,
                $row->material_es,
                $row->hose_diameter,
                $row->length,
                $row->carton_size,
                $row->carton_weight,
                $row->ship_via,
                $row->marketing_desc_en,
                $row->feature_1_en,
                $row->feature_2_en,
                $row->feature_3_en,
                $row->feature_4_en,
                $row->feature_5_en,
                $row->feature_6_en,
                $row->feature_7_en,
                $row->feature_8_en,
                $row->feature_9_en,
                $row->feature_10_en,
                $row->feature_11_en,
                $row->feature_12_en,
                $row->feature_13_en,
                $row->marketing_desc_es,
                $row->feature_1_es,
                $row->feature_2_es,
                $row->feature_3_es,
                $row->feature_4_es,
                $row->feature_5_es,
                $row->feature_6_es,
                $row->feature_7_es,
                $row->feature_8_es,
                $row->feature_9_es,
                $row->feature_10_es,
                $row->feature_11_es,
                $row->feature_12_es,
                $row->feature_13_es,
                $row->marketing_app_1,
                $row->marketing_app_2,
                $row->marketing_app_3,
                $row->marketing_app_4,
                $row->marketing_app_5,
                $row->marketing_app_6,
                $row->marketing_app_7,
                $row->marketing_app_8,
                $row->marketing_app_9,
                $row->marketing_app_10,
                $row->marketing_app_11,
                $row->marketing_app_12,
                $row->marketing_app_13,
                $row->marketing_app_14,
                $row->marketing_app_15,
                $row->marketing_app_16,
                $row->marketing_app_17,
                $row->marketing_app_18,
                $row->marketing_app_19,
                $row->marketing_app_20,
                $row->marketing_app_21,
                $row->marketing_app_21,
                $row->marketing_app_22,
                $row->marketing_app_23,
                $row->certification,
                $row->ul_listed_as_94v,
                $row->prop_65_warn,
                $row->reach,
                $row->nafta,
                $row->rohs,
                $row->a,
                $row->f,
                $row->d,
                $row->m,
                $row->ru,
                $row->class_insstringe_diameter,
                $row->class_temprature,
                $row->class_length,
                $row->datasheet,
                $row->datasheet_es,
                $row->customer_provstringed_doc,
                $row->media_other,
                $row->media_photo1,
                $row->media_photo2,
                $row->media_photo3,
                $row->media_photo4,
                $row->media_cuffdrawing,
                $row->media_customerdrawing1,
                $row->media_customerdrawing2,
                $row->media_profiledrawing,
            );
            $csv->insertOne($d);
        }

        // Output CSV
        $csv->output('products-'.date('YmdHis').'.csv');
    }

    public function notifyNewUserEmail()
    {
        $userEmail = 'khuram.saleem3326@gmail.com';
        $r = Mail::to($userEmail)->send(new WelcomeUserEmail());
        if($r){
            return true;
        }
        return false;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function updateUserPassword(Request $request){
        $response = array(
            'message' => '',
        );
        if($request->email != '' && $request->password != ''){
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->password = Hash::make($request->password);
                if ($user->save()) {
                    $response['message'] = "User password updated successfully.";
                } else {
                    $response['message'] = $user->getErrors();
                }
            } else {
                $response['message'] = "User not found against this email.";
            }
        }
        return response()->json(['request' => $response]);
    }

    public function get_userByID($user_id){
        $user = User::findOrFail($user_id);
        return $user;
    }

    public function notifyUseronProductUpdates(){
        $users = User::where('email_notification', 1)->get();
        $notify=false;
        $user_info = array(
            'email' => '',
            'name' => ''
        );
        $product_IDs = array();
        foreach ($users as $user) 
        {   
            $user_info['email'] = $user->email;
            $user_info['name'] = $user->name;

            $syndication = DB::table('distributor_syndication')->where('user_id', $user->id)->first();
            $configurations = DB::table('distributor_syndication_configurations')->select('prod_id')->where('synd_id', $syndication->synd_id)->get();
            if ($configurations) {
                foreach($configurations as $val){
                    $products = DB::table('tbl_product_information')->where('product_id', $val->prod_id)->where('is_updated', 1)->select('id', 'sku')->get();
                    if(count($products) >= 1){
                        $notify=true;
                        $product_IDs[] = $val->prod_id;
                    }
                    
                }
            }
            if($notify===true){
                // notify user
                $mail_text = "Hello ".$user_info['name'].", \n";
                $mail_text .= "A product in your catalogue has been updated. Login to application and get a fresh export from the following url: \n";
                $mail_text .= 'https://syndicate.flexaustpim.com/generate-csv?t='.date('YmdHis');
                $email = $user_info['email'];
                Mail::raw($mail_text, function ($message) use ($email) {
                    $message->to($email)->subject('FlexaustPIM - Syndication Product Update');
                });
            }
        }
        //var_dump($product_IDs); //exit;
        // Updating product flag
        foreach($product_IDs as $prod){
            DB::table('tbl_product_information')->where('product_id', $prod)->update(['is_updated' => 0]);
        }
    }
}

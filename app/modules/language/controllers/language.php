<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class language extends MX_Controller {
	public $table;
	public $columns;
	public $module_name;
	public $module_icon;

	public function __construct(){
		parent::__construct();
		$this->load->model(get_class($this).'_model', 'model');

		//Config Module
		$this->tb_language_list       = LANGUAGE_LIST;
		$this->tb_language           = LANGUAGE;
		$this->module_icon = "fa fa-language";
		$this->columns = array(
			"name"      => lang("Name"),
			"code"      => lang("Code"),
			"icon"      => lang("Icon"),
			"default"   => lang("Default"),
			"created"   => lang("Created"),
			"status"    => lang("Status"),
		);
	}

	public function index(){
		if (get_role('user') || get_role('supporter')) {
			redirect(cn('statistics'));
		}

		$data = array(
			"columns" 	=> $this->columns,
			"module"  	=> get_class($this),
			"languages" => $this->model->fetch("*", $this->tb_language_list),
		);
		$this->template->build('index', $data);
	}

	public function update($ids = ''){
		/*----------  import all lang line to default lang  ----------*/
		// import_lang_line(APPPATH.'../');
		// create_language();

		$data = array(
			"module"  		 => get_class($this),
			"module_icon"    => $this->module_icon,
			"default_lang"   => create_default_lang(),
		);

		if(!empty($ids)){
			$language = $this->model->get('*', $this->tb_language_list, "ids = '{$ids}'");
			if(!empty($language)){
				$data['lang']      = $language;
				$data['lang_db']   = [];
				//get data old version
				$old_path = FCPATH."app/language/tmp/lang_".$language->code.".txt";
				if (file_exists($old_path)) {
					$data['lang_db']   = get_json_content( $old_path );
				}
				// New version 
				$new_path = FCPATH."app/language/data/". $language->code ."_lang.php";
				if (!$data['lang_db'] && file_exists($new_path)) {
					include($new_path);
					$data['lang_db'] = (object)$lang;
				}
			}else{
				load_404();
			}
		}
		$this->template->build('update', $data);
	}

	public function ajax_update($ids = ""){
		if (get_role('user')) {
			redirect(cn('statistics'));
		}
		$language_code       = post('language_code');
		$country_code        = post('country_code');
		$status    		     = (int)post('status');
		$default    		 = (int)post('default');
		$langs               = $_POST['lang'];
		$data = array(
			"code"               => $language_code,
			"country_code"       => $country_code,
			"status"             => $status,
			"is_default"         => $default,
		);
		// check exists language code
		if(!language_codes($language_code)){
			ms(array(
				"status"  => "error",
				"message" => lang("language_code_does_not_exists")
			));
		}

		// Check lang defaut
		if($default == 1){
			$checkLangDefault = $this->model->fetch('*',$this->tb_language_list, "is_default = 1");
			if(!empty($checkLangDefault)){
				$this->db->update($this->tb_language_list, array('is_default' => 0));
			}
		}
		
		if ($ids != '') {
			// check lang exists
			$checkLangList = $this->model->get('code, ids', $this->tb_language_list, "ids = '{$ids}'");
			if(!empty($checkLangList)){
				$this->db->update($this->tb_language_list, $data, ['ids' => $ids]);
				//creating array of language file
	            $lang_path = FCPATH."app/language/data/".$language_code ."_lang.php";
	            create_lang_file($lang_path, $langs);

				if (file_exists($lang_path)) {
					$this->db->delete($this->tb_language, ["lang_code" => $language_code]);
				}
				
				// Delete old lang file
				$old_path = FCPATH."app/language/tmp/lang_".$language_code.".txt";
				if (file_exists($old_path)) {
					@unlink($old_path);
				}
				
				ms(array(
					'status'  => 'success',
					'message' => lang("Update_successfully"),
				));
			}

		} else {
			$checklang = $this->model->get('*', $this->tb_language_list, "code = '{$language_code}'");
			if(!empty($checklang)){
				ms(array(
					'status'  => 'error',
					'message' => lang("language_code_already_exists"),
				));
			}
			$data['ids']     = ids();
			$data['created'] = NOW;
			$this->db->insert($this->tb_language_list, $data);

			//create language file
			if(is_array($langs) && !empty($langs)){
				//creating array of language file
	            $lang_path = FCPATH."app/language/data/".$language_code ."_lang.php";
	            create_lang_file($lang_path, $langs);
				ms(array(
					'status'  => 'success',
					'message' => lang("Update_successfully"),
				));
			}
		}
	}

	public function export(){
		export_csv($this->table);
	}

	public function ajax_delete_item($ids = ""){
		$this->model->delete($this->tb_language_list, $ids, false);
	}

	public function set_language($ids = ""){
		$checkLang = $this->model->get('*', $this->tb_language_list, "ids = '{$ids}'");

		if(!empty($checkLang)){
			unset_session('langCurrent');
			set_session('langCurrent',$checkLang);
			ms(array(
				'status'  => 'success',
				'message' => lang("Update_successfully"),
			));
		}
	}
}
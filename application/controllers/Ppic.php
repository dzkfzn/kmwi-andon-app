<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/BaseController.php';

class Ppic extends BaseController
{

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));
		$this->lang->load('auth');
		$this->load->model('Master_model');
		if (!$this->ion_auth->logged_in()) {
			redirect('production/login', 'refresh');
		}
		if (!$this->ion_auth->is_ppic()) {
			show_error('You must be a Ppic to view this page.');
		}
	}

	public function show404()
	{
		$this->set_global('Error', 'Error');
		$this->loadViews("errors/custom/404", $this->global, NULL, NULL);

	}

	public function is_any_verified($id, $table)
	{
		if (is_null_station($this->Master_model->any_select($this->sp_detail, $table, array($id)))) {
			$this->set_global('Admin | Manage Master - ' . $table, 'Manage Master Data ' . $table);
			$this->loadViews("errors/custom/404", $this->global, NULL, NULL);
			return FALSE;
		}
		return TRUE;
	}

	public function index()
	{
		redirect('production', 'refresh');
	}


	public function schedule_today()
	{
		//global var
		$this->global['gPageTitle'] = 'PPIC | Manage Schedule - Schedule Today View';
		$this->global['gContentTitle'] = 'Manage Schedule';
		$this->global['gCardTitle'] = 'Schedule Today List';
		// set the flash data error message if there is one
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

		//list the stations
		$this->data['schedule_today'] = $this->Master_model->any_select($this->sp_list, $this->tbl_schedule . $this->desc_today);
		$this->loadViews("ppic/schedule_list_today", $this->global, $this->data, NULL);
	}


	public function station_inactive($id)
	{
		if (!$this->is_any_verified($id, $this->tbl_station)) return;
		$this->Master_model->any_exec(array($id, $this->session->userdata('username')), $this->sp_inactive, $this->tbl_station);
		$this->ion_auth->set_message('Shift Set to Inactive');
		$this->session->set_flashdata('message', $this->ion_auth->messages());
		redirect("production/station", 'refresh');
	}

	public function schedule_create_step1()
	{
		$this->form_validation->set_rules('pro_date', 'Production Date', 'trim|required|callback_is_greater_today');
		$this->form_validation->set_rules('shift', ' Shift', 'required|trim');
		$this->form_validation->set_rules('product', 'Product', 'trim|required');
		$this->form_validation->set_rules('scheme', 'Schema', 'trim|required');
		$this->form_validation->set_rules('plan', 'Plan', 'trim|required|is_natural');


		if ($this->form_validation->run() === TRUE) {
			$id = $this->uuid->v4();
			$pro_date = $this->input->post('pro_date');
			$shift = $this->input->post('shift');
			$product = $this->input->post('product');
			$scheme = $this->input->post('scheme');
			$plan = $this->input->post('plan');;
			$creadate = date('m/d/Y h:i:s a', time());
			$creaby = $this->session->userdata('username');
			$data = array($id, $shift, $scheme, $product, $pro_date, $plan, $creaby, $creadate);
		}

		if ($this->form_validation->run() === TRUE && $this->Master_model->any_exec($data, $this->sp_insert, $this->tbl_schedule)) {
			{
				$token = bin2hex(random_bytes(40 / 2));
				$this->session->set_userdata('token_schedule1', $token);
				redirect('production/schedule/add/2/' . $id . '/' . $scheme, 'refresh');
			}
		} else {
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

			foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_shift) as $row) {
				$this->data['option_shift'][$row->sif_id] = $row->sif_name . ' (' . print_beauty_time($row->sif_start_date) . ' - ' . print_beauty_time($row->sif_end_date) . ')';
			}
			foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_product) as $row) {
				$this->data['option_product'][$row->pro_id] = $row->pro_name . ' - ' . $row->pro_type;
			}
			foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_scheme) as $row) {
				$this->data['option_scheme'][$row->sce_id] = $row->sce_name;
			}

			$this->data['dropdown_extra'] =
				'class="selectpicker" 
				 data-style="select-with-transition"
				 title="Choose"
				 data-size="7"
				 ';

			$this->data['pro_date'] = array(
				'name' => 'pro_date',
				'id' => 'pro_date',
				'class' => 'form-control datepicker',
				'type' => 'text',
				'maxLength' => 10,
				'required' => TRUE,
				'value' => $this->form_validation->set_value('pro_date'),
			);
			$this->data['plan'] = array(
				'name' => 'plan',
				'id' => 'plan',
				'class' => 'form-control',
				'type' => 'number',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('plan'),
			);

			$this->data['form_attribute'] = array(
				'id' => 'FormValidation',
				'class' => 'form-horizontal'
			);

			$this->global['gPageTitle'] = 'PPIC | Schedule';
			$this->global['gContentTitle'] = 'Manage Schedule';
			$this->global['gCardTitle'] = 'Add Schedule';
			$this->loadViews("ppic/schedule_add", $this->global, $this->data, NULL);

		}
	}

//	public function schedule_edit_step1($id){
//		if (!$this->is_any_verified($id, $this->tbl_schedule))
//			return;
//		$schedule = $this->Master_model->any_select($this->sp_detail, $this->tbl_schedule, array($id));
//
////		if (!$this->session->has_userdata('token_add_schedule'))
//
//
//		$this->form_validation->set_rules('pro_date', 'Production Date', 'trim|required|callback_is_greater_today');
//		$this->form_validation->set_rules('shift', ' Shift', 'required|trim');
//		$this->form_validation->set_rules('product', 'Product', 'trim|required');
//		$this->form_validation->set_rules('scheme', 'Schema', 'trim|required');
//		$this->form_validation->set_rules('plan', 'Plan', 'trim|required|is_natural');
//
//
//		if ($this->form_validation->run() === TRUE) {
//			$pro_date = $this->input->post('pro_date');
//			$shift = $this->input->post('shift');
//			$product = $this->input->post('product');
//			$scheme = $this->input->post('scheme');
//			$plan = $this->input->post('plan');;
//			$creadate = date('m/d/Y h:i:s a', time());
//			$creaby = $this->session->userdata('username');
//			$data = array($id, $shift, $scheme, $product, $pro_date, $plan, $creaby, $creadate);
//		}
//
//		if ($this->form_validation->run() === TRUE && $this->Master_model->any_exec($data, $this->sp_insert, $this->tbl_schedule)) {
//			{
//				$token = bin2hex(random_bytes(40 / 2));
//				$this->session->set_userdata('token_add_schedule', $token);
//				redirect('production/schedule/add/2/' . $id . '/' . $scheme, 'refresh');
//			}
//		} else {
//			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));
//
//			foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_shift) as $row) {
//				$this->data['option_shift'][$row->sif_id] = $row->sif_name . ' (' . print_beauty_time($row->sif_start_date) . ' - ' . print_beauty_time($row->sif_end_date) . ')';
//			}
//			foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_product) as $row) {
//				$this->data['option_product'][$row->pro_id] = $row->pro_name . ' - ' . $row->pro_type;
//			}
//			foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_scheme) as $row) {
//				$this->data['option_scheme'][$row->sce_id] = $row->sce_name;
//			}
//
//			$this->data['dropdown_extra'] =
//				'class="selectpicker"
//				 data-style="select-with-transition"
//				 title="Choose"
//				 data-size="7"
//				 ';
//
//			$this->data['pro_date'] = array(
//				'name' => 'pro_date',
//				'id' => 'pro_date',
//				'class' => 'form-control datepicker',
//				'type' => 'text',
//				'maxLength' => 10,
//				'required' => TRUE,
//				'value' => $this->form_validation->set_value('pro_date'),
//			);
//			$this->data['plan'] = array(
//				'name' => 'plan',
//				'id' => 'plan',
//				'class' => 'form-control',
//				'type' => 'number',
//				'required' => TRUE,
//				'value' => $this->form_validation->set_value('plan'),
//			);
//			$this->data['plan'] = array(
//				'name' => 'plan',
//				'class' => 'form-control',
//				'type' => 'number',
//				'required' => TRUE,
//				'value' => $this->form_validation->set_value('plan'),
//			);
//
//			$this->data['form_attribute'] = array(
//				'id' => 'FormValidation',
//				'class' => 'form-horizontal'
//			);
//
//			$this->global['gPageTitle'] = 'PPIC | Schedule';
//			$this->global['gContentTitle'] = 'Manage Schedule';
//			$this->global['gCardTitle'] = 'Add Schedule';
//			$this->loadViews("ppic/schedule_add", $this->global, $this->data, NULL);
//
//		}
//	}

	public function schedule_create_step2($id = NULL, $id_schema = NULL)
	{
		if (!$this->is_any_verified($id, $this->tbl_schedule) || !$this->is_any_verified($id_schema, $this->tbl_scheme))
			return;

		if (!$this->session->has_userdata('token_schedule1'))
			show_error('You must input from beginning.');

		$this->form_validation->set_rules('cycle_time[]', 'Cycle Time', 'trim|required|max_length[8]');
		$this->data['stations'] = $this->Master_model->any_select($this->sp_list, $this->tbl_scheme_detail, array($id_schema), TRUE);

		if ($this->form_validation->run() === TRUE) {
			$this->session->unset_userdata('token_schedule1');
			$cycle_time = $this->input->post('cycle_time');

			$i = 0;
			foreach ($this->data['stations'] as $row) {
				$this->Master_model->any_exec(array($this->uuid->v4(), $id, $row->sta_id, $cycle_time[$i]), $this->sp_insert, $this->tbl_production);
				$i++;
			}
			$token = bin2hex(random_bytes(40 / 2));
			$this->session->set_userdata('token_schedule2', $token);
			redirect('production/schedule/add/3/' . $id, 'refresh');
		} else {
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

			$this->data['cycle_time'] = array(
				'name' => 'cycle_time[]',
				'id' => 'cycle_time',
				'class' => 'form-control timepickersecond',
				'type' => 'text',
				'required' => 'required',
				'maxLength' => 8
			);

			$this->data['form_attribute'] = array(
				'id' => 'FormValidation',
				'class' => 'form-horizontal'
			);

			$this->global['gPageTitle'] = 'PPIC | Schedule';
			$this->global['gContentTitle'] = 'Manage Schedule';
			$this->global['gCardTitle'] = 'Add Cycle Time Each Station';
			$this->loadViews("ppic/schedule_add2", $this->global, $this->data, NULL);

		}
	}

	public function schedule_create_step3($id)
	{

		if (!$this->is_any_verified($id, $this->tbl_schedule))
			return;

		if (!$this->session->has_userdata('token_schedule2'))
			show_error('You must input from beginning.');

		$this->data['productions'] = $this->Master_model->any_select($this->sp_list, $this->tbl_production, array($id), TRUE);
		$this->data['schedule'] = $this->Master_model->any_select($this->sp_detail, $this->tbl_schedule, array($id));

		if ($this->form_validation->run() === TRUE) {

			$this->Master_model->any_exec(array($this->uuid->v4(), $id, $row->sta_id, $cycle_time[$i]), $this->sp_insert, $this->tbl_production);
			$this->session->unset_userdata('token_schedule2');
			redirect('production/schedule/add/3/' . $id, 'refresh');
		} else {
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));


			$this->data['cycle_time'] = array(
				'name' => '',
				'id' => 'cycle_time',
				'type' => 'text',
				'class' => 'form-control',
				'readonly' => 'readonly',
				'required' => 'required',
			);

			$this->data['pro_date'] = array(
				'name' => 'pro_date',
				'id' => 'pro_date',
				'class' => 'form-control',
				'readonly' => 'readonly',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('pro_date', $this->data['schedule']->sch_production_date),
			);
			$this->data['plan'] = array(
				'name' => 'plan',
				'id' => 'plan',
				'readonly' => 'readonly',
				'class' => 'form-control',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('plan', $this->data['schedule']->sch_plan),
			);
			$this->data['shift'] = array(
				'name' => 'shift',
				'id' => 'shift',
				'readonly' => 'readonly',
				'class' => 'form-control',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('shift', $this->data['schedule']->sif_name),
			);
			$this->data['product'] = array(
				'name' => 'product',
				'id' => 'product',
				'readonly' => 'readonly',
				'class' => 'form-control',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('product', $this->data['schedule']->pro_name),
			);
			$this->data['scheme'] = array(
				'name' => 'scheme',
				'id' => 'scheme',
				'readonly' => 'readonly',
				'class' => 'form-control',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('scheme', $this->data['schedule']->sce_name),
			);

			$this->data['form_attribute'] = array(
				'id' => 'FormValidation',
				'class' => 'form-horizontal'
			);

			$this->global['gPageTitle'] = 'PPIC | Schedule';
			$this->global['gContentTitle'] = 'Manage Schedule';
			$this->global['gCardTitle'] = 'Add Cycle Time Each Station';
			$this->loadViews("ppic/schedule_add3", $this->global, $this->data, NULL);

		}

	}

	public function schedule_create($step, $id = NULL, $id_schema = NULL)
	{
		if ($step == 1)
			$this->schedule_create_step1();
		else if ($step == 2)
			$this->schedule_create_step2($id, $id_schema);
		else if ($step == 3)
			$this->schedule_create_step3($id);
		else
			redirect('production/schedule', 'refresh');

	}

	public function scheme_create()
	{
		$this->form_validation->set_rules('name', ' Name', 'required|trim|max_length[20]|is_unique[prd_msscheme.sce_name]');
		$this->form_validation->set_rules('output', 'Output', 'trim|max_length[25]');
		$this->form_validation->set_rules('station[]', 'Station', 'trim|required');

		if ($this->form_validation->run() === TRUE) {
			$id = $this->uuid->v4();
			$name = ucwords($this->input->post('name'));
			$output = ucwords($this->input->post('output'));
			$station = $this->input->post('station');
			$creaby = $this->session->userdata('username');
			$creadate = date('m/d/Y h:i:s a', time());
			$data = array($id, $name, $output, $creaby, $creadate, 0);
		}
		if ($this->form_validation->run() === TRUE && $this->Master_model->any_exec($data, $this->sp_insert, $this->tbl_scheme)) {
			{
				foreach ($station as $key => $value) {
					$this->Master_model->any_exec(array($this->uuid->v4(), $value, $id), $this->sp_insert, $this->tbl_scheme_detail);
				}
				$this->ion_auth->set_message('scheme Inserted Succesfully');
				$this->session->set_flashdata('message', $this->ion_auth->messages());
				redirect('master/scheme', 'refresh');
			}
		} else {
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

			foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_station) as $row) {
				$this->data['option_station'][$row->sta_id] = $row->sta_name . ' - ' . $row->sta_type;
			}
			$this->data['station_extra'] =
				'class="selectpicker" 
				 data-style="select-with-transition"
				 title="Choose Station"
				 data-size="7"
				 id="station"
				 ';

			$this->data['name'] = array(
				'name' => 'name',
				'id' => 'name',
				'class' => 'form-control',
				'type' => 'text',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('name'),
			);
			$this->data['output'] = array(
				'name' => 'output',
				'id' => 'output',
				'class' => 'form-control',
				'type' => 'text',
				'required' => TRUE,
				'value' => $this->form_validation->set_value('output'),
			);

			$this->data['form_attribute'] = array(
				'id' => 'FormValidation',
				'class' => 'form-horizontal'
			);

			$this->global['gPageTitle'] = 'Admin | Manage Master - scheme';
			$this->global['gContentTitle'] = 'Manage Master Data scheme';
			$this->global['gCardTitle'] = 'Add scheme';
			$this->loadViews("admin/scheme_add", $this->global, $this->data, NULL);

		}


	}


	public function scheme_edit($id, $is_editable = TRUE)
	{

		if (!$this->is_any_verified($id, $this->tbl_scheme)) return;
		$scheme = $this->Master_model->any_select($this->sp_detail, $this->tbl_scheme, array($id));


		// validate form input
		$this->form_validation->set_rules('name', ' Name', 'required|trim|max_length[36]');
		$this->form_validation->set_rules('output', 'output', 'trim|max_length[36]');

		if (isset($_POST) && !empty($_POST)) {
			// do we have a valid request?
			if ($id != $this->input->post('id')) {
				show_error($this->lang->line('error_csrf'));
			}

			if ($this->form_validation->run() === TRUE) {

				$data = array(
					$id,
					$this->input->post('name'),
					$this->input->post('output'),
					$creaby = $this->session->userdata('username'),
				);
				$station = $this->input->post('station');
				// check to see if we are updating the user
				if ($this->Master_model->any_exec($data, $this->sp_update, $this->tbl_scheme)) {
					$this->Master_model->any_exec(array($id), $this->sp_delete, $this->tbl_scheme_detail);
					foreach ($station as $key => $value) {
						$this->Master_model->any_exec(array($this->uuid->v4(), $value, $id), $this->sp_insert, $this->tbl_scheme_detail);
					}
					// redirect them back to the admin page if admin, or to the base url if non admin
					$this->ion_auth->set_message('scheme Updated Succesfully');
					$this->session->set_flashdata('message', $this->ion_auth->messages());
					redirect('production/scheme');
				} else {
					// redirect them back to the admin page if admin, or to the base url if non admin
					$this->ion_auth->set_message('Failed to Update');
					$this->session->set_flashdata('message', $this->ion_auth->messages());
					redirect('production/scheme');
				}

			}
		}

		// set the flash data error message if there is one
		$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));


		if ($is_editable) {
			$this->set_global('Admin | Manage Master - scheme', 'Manage Master Data scheme', 'Edit scheme');
			$is_disabled = 'enabled';
		} else {
			$is_disabled = 'disabled';
			$this->set_global('Admin | Manage Master - scheme', 'Manage Master Data scheme', 'Detail scheme');
			$this->data['creaby'] = array(
				'class' => 'form-control',
				'disabled' => 'disabled',
				'output' => 'text',
				'value' => $this->form_validation->set_value('creaby', $scheme->sce_creaby)
			);
			$this->data['creadate'] = array(
				'class' => 'form-control',
				'disabled' => 'disabled',
				'output' => 'text',
				'value' => $this->form_validation->set_value('creadate', time_elapsed_string($scheme->sce_creadate) . ' (' . print_beauty_date($scheme->sce_creadate) . ')')
			);
			$this->data['modiby'] = array(
				'class' => 'form-control',
				'disabled' => 'disabled',
				'output' => 'text',
				'value' => $this->form_validation->set_value('modiby', is_null_modiby($scheme->sce_modiby))
			);
			$this->data['modidate'] = array(
				'class' => 'form-control',
				'disabled' => 'disabled',
				'output' => 'text',
				'value' => $this->form_validation->set_value('modidate', is_null_modiby($scheme->sce_modidate, TRUE) . ' (' . print_beauty_date($scheme->sce_modidate) . ')')
			);
			$this->data['status'] = array(
				'class' => 'form-control',
				'disabled' => 'disabled',
				'output' => 'text',
				'value' => $this->form_validation->set_value('status', ($scheme->sce_is_deleted) ? 'Inactive' : 'Active')
			);
		}

		foreach ($this->Master_model->any_select($this->sp_list, $this->tbl_station) as $row) {
			$this->data['option_station'][$row->sta_id] = $row->sta_name . ' - ' . $row->sta_type;
		}
		$this->data['schema_detail'] = $this->Master_model->any_select($this->sp_list, $this->tbl_scheme_detail, array($id), TRUE);
		foreach ($this->data['schema_detail'] as $row) {
			$this->data['station_selected'][$row->sta_id] = $row->sta_id;
		}
		$this->data['station_extra'] =
			'class="selectpicker" 
				 data-style="select-with-transition"
				 title="Choose Station"
				 data-size="7"
				 id="station"
				 ';


		// pass the user to the view
		$this->data['scheme'] = $scheme;
		$this->data['name'] = array(
			'name' => 'name',
			'id' => 'name',
			$is_disabled => $is_disabled,
			'class' => 'form-control',
			'output' => 'text',
			'required' => TRUE,
			'value' => $this->form_validation->set_value('name', $scheme->sce_name)
		);
		$this->data['output'] = array(
			'name' => 'output',
			'id' => 'output',
			$is_disabled => $is_disabled,
			'class' => 'form-control',
			'output' => 'text',
			'value' => $this->form_validation->set_value('output', $scheme->sce_output)
		);

		$this->data['form_attribute'] = array(
			'id' => 'FormValidation',
			'class' => 'form-horizontal'
		);

		if ($is_editable)
			$this->loadViews("admin/scheme_edit", $this->global, $this->data, NULL);
		else
			$this->loadViews("admin/scheme_detail", $this->global, $this->data, NULL);

	}


	function is_time_format($time)
	{
		//if time is invalid, it will return false and error message
		if (!DateTime::createFromFormat('g:i A', $time)) {
			$this->form_validation->set_message('is_time_format', '%s invalid format');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	function is_greater_today($date)
	{
		$date_now = date("d/m/Y", strtotime("-1 days"));
		if ($date_now >= $date) {
			$this->form_validation->set_message('is_greater_today', '%s field must greater than today');
			return FALSE;
		} else {
			return TRUE;
		}
	}
}

/* End of file Master.php */

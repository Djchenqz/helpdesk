<?php

namespace App\Http\Controllers\Agent\helpdesk;

// controllers
use App\Http\Controllers\Controller;
// requests
use App\Http\Requests\helpdesk\OrganizationRequest;
/* include organization model */
use App\Http\Requests\helpdesk\OrganizationUpdate;
// models
/* Define OrganizationRequest to validate the create form */
use App\Model\helpdesk\Agent_panel\Organization;
use App\Model\helpdesk\Agent_panel\User_org;
/* Define OrganizationUpdate to validate the create form */
use App\User;
// classes
use Exception;
use Illuminate\Http\Request;
use Lang;
use App\Model\helpdesk\Manage\Sla_plan;
use App\Model\helpdesk\Agent\Teams;
use App\Model\helpdesk\Agent\Assign_team_agent;
use DB;

/**
 * OrganizationController
 * This controller is used to CRUD organization detail.
 *
 * @author      Ladybird <info@ladybirdweb.com>
 */
class OrganizationController extends Controller
{
    /**
     * Create a new controller instance.
     * constructor to check
     * 1. authentication
     * 2. user roles
     * 3. roles must be agent.
     *
     * @return void
     */
    public function __construct()
    {
        // checking for authentication
        $this->middleware('auth');
        // checking if the role is agent
        $this->middleware('role.agent');
    }

    /**
     * Display a listing of the resource.
     *
     * @param type Organization $org
     *
     * @return type Response
     */
    public function index()
    {
        try {
            /* get all values of table organization */
            return view('themes.default1.agent.helpdesk.organization.index');
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * This function is used to display the list of Organizations.
     *
     * @return datatable
     */
    public function org_list()
    {
        // chumper datable package call to display Advance datatable
        return \Datatable::collection(Organization::all())
                        /* searchable name */
                        ->searchColumns('name')
                        /* order by name and website */
                        ->orderColumns('name', 'website')
                        /* column name */
                        ->addColumn('name', function ($model) {
                            // return $model->name;
                            if (strlen($model->name) > 20) {
                                $orgname = substr($model->name, 0, 25);
                                $orgname = substr($orgname, 0, strrpos($orgname, ' ')).' ...';
                            } else {
                                $orgname = $model->name;
                            }

                            return $orgname;
                        })
                        /* column website */
                        ->addColumn('website', function ($model) {
                            $website = $model->website;

                            return $website;
                        })
                        /* column phone number */
                        ->addColumn('phone', function ($model) {
                            $phone = $model->phone;

                            return $phone;
                        })
			/* column internal_notes 
			->addColumn('internal_notes', function($model){
			    $notes = $model->internal_notes;
			    return $notes;
			})*/
			/* column sales */
			->addColumn('sales', function($model){
			    $sales = json_decode($model->internal_notes)->sales;
			    return $sales;
			})
			/* column PM */
			->addColumn('PM', function($model){
			    $PM = json_decode($model->internal_notes)->PM;
			    return $PM;
			})
			/* column SLA */
			->addColumn('SLA', function($model){
			    $SLA = json_decode($model->internal_notes)->SLA;
			    return $SLA;
			})

			/* column Transfer */
			->addColumn('Transfer', function($model){
			    $Transfer = json_decode($model->internal_notes)->Transfer;
			    switch($Transfer){
				case '0':
				    return '立即升级到AWS';
				case '-1':
				    return '不升级到AWS';
				case 'N';
				    return '创建N分钟未解决自动升级到AWS';
			}
			  
			})
			/* column type */
			->addColumn('type', function($model){
			    $type = json_decode($model->internal_notes)->type;
			    switch($type){
				case '0':
				    return '普通客户';
				case '1':
				    return '代付客户';
				case '2':
				    return '泰岳支持客户';
				case '3': 
				    return '商业支持客户';
				case '4':
				    return '企业支持客户';
			}
			})



                        /* column action buttons */
                        ->addColumn('Actions', function ($model) {
                            // displaying action buttons
                            // modal popup to delete data
                            return '<span  data-toggle="modal" data-target="#deletearticle'.$model->id.'"><a href="#" ><button class="btn btn-danger btn-xs"></a> '.\Lang::get('lang.delete').' </button></span>&nbsp;<a href="'.route('organizations.edit', $model->id).'" class="btn btn-warning btn-xs">'.\Lang::get('lang.edit').'</a>&nbsp;<a href="'.route('organizations.show', $model->id).'" class="btn btn-primary btn-xs">'.\Lang::get('lang.view').'</a>
				<div class="modal fade" id="deletearticle'.$model->id.'">
			        <div class="modal-dialog">
			            <div class="modal-content">
                			<div class="modal-header">
                    			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    			<h4 class="modal-title">Are You Sure ?</h4>
                			</div>
                			<div class="modal-body">
                				'.$model->user_name.'
                			</div>
                			<div class="modal-footer">
                    			<button type="button" class="btn btn-default pull-left" data-dismiss="modal" id="dismis2">Close</button>
                    			<a href="'.route('org.delete', $model->id).'"><button class="btn btn-danger">delete</button></a>
                			</div>
            			</div><!-- /.modal-content -->
        			</div><!-- /.modal-dialog -->
    			</div>';
                        })
                        ->make();
    }

    /**
     * Show the form for creating a new organization.
     *
     * @return type Response
     */
    public function create(User $user,Sla_plan $sla,Teams $team,Assign_team_agent $assign_team_agent)
    {

        try {
	    $team_id = $team->where('name','=','销售Team')->first()->id;
	    $assign_team_agents = $assign_team_agent->where('team_id','=',$team_id)->get()->toArray();
	    $agent_id = $assign_team_agents[0]['agent_id'];
	    $name = $user->where('id','=',$agent_id)->get();	
	    $sales =  DB::table('team_assign_agent')->join('users','users.id','=','team_assign_agent.agent_id')->where('team_assign_agent.team_id','=',$team_id)->get();
	    // dd($sales);	      
	    $agents = $user->where('role','=','agent')->get();
	    // dd($agents);
	    $slas = $sla->get();	    
            return view('themes.default1.agent.helpdesk.organization.create',compact('agents','slas','sales'));
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Store a newly created organization in storage.
     *
     * @param type Organization        $org
     * @param type OrganizationRequest $request
     *
     * @return type Redirect
     */
    public function store(Organization $org, OrganizationRequest $request)
    {
	
        try {
            /* Insert the all input request to organization table */
            /* Check whether function success or not */
	    /* add internal_notes field */
	    // dd($user->where('role','=','agent')->get());		    

	    $note = json_encode(['sales'=>$request->input('sales'),'PM'=>$request->input('PM'),'SLA'=>$request->input('SLA'),'Transfer'=>$request->input('Transfer'),'type'=>$request->input('type')]);
	    $input_data = $request->except(['sales','PM','SLA','Transfer','type']);


            // if ($org->fill($request->input())->save() == true) {
	    if($org->fill(array_merge($input_data, ['internal_notes'=>$note]))->save()==true){
                /* redirect to Index page with Success Message */
                return redirect('organizations')->with('success', Lang::get('lang.organization_created_successfully'));
            } else {
                /* redirect to Index page with Fails Message */
                return redirect('organizations')->with('fails', Lang::get('lang.organization_can_not_create'));
            }
        } catch (Exception $e) {
            /* redirect to Index page with Fails Message */
            return redirect('organizations')->with('fails', Lang::get('lang.organization_can_not_create'));
        }
    }

    /**
     * Display the specified organization.
     *
     * @param type              $id
     * @param type Organization $org
     *
     * @return type view
     */
    public function show($id, Organization $org)
    {
	
        try {
            /* select the field by id  */
            $orgs = $org->whereId($id)->first();
            /* To view page */
            return view('themes.default1.agent.helpdesk.organization.show', compact('orgs'));
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified organization.
     *
     * @param type              $id
     * @param type Organization $org
     *
     * @return type view
     */
    public function edit($id, Organization $org,User $user,Sla_plan $sla)
    {

        try {
            /* select the field by id  */
            $orgs = $org->whereId($id)->first();
	    // dd($orgs);
	   
	    $internal_notes = $orgs->internal_notes;
	    
	    $data = json_decode($internal_notes);
	    
	    $orgs->sales = $data->sales;
	    $orgs->PM = $data->PM;
	    $orgs->SLA = $data->SLA;
	    $orgs->Transfer = $data->Transfer;
	    $orgs->type = $data->type;
	   // get agents
	    $agents = $user->where('role','=','agent')->get();
	   // get sla plan
	    $slas = $sla->get();
	   /* $internal_notes = $orgs->internal_notes;
	    return $internal_notes;   
	    $notes_arr = json_decode($internal_notes);
	    dd($notes_arr);
	    $sales = $notes_arr->'sales';
	    dd($sales);*/
            /* To view page */
            return view('themes.default1.agent.helpdesk.organization.edit', compact('orgs','agents','slas'));
        } catch (Exception $e) {
            return redirect()->back()->with('fails', $e->getMessage());
        }
    }

    /**
     * Update the specified organization in storage.
     *
     * @param type                    $id
     * @param type Organization       $org
     * @param type OrganizationUpdate $request
     *
     * @return type Redirect
     */
    public function update($id, Organization $org, OrganizationUpdate $request)
    {
        try {
            /* select the field by id  */
            $orgs = $org->whereId($id)->first();
	   
            /* update the organization table   */
            /* Check whether function success or not */
	    
	    
	    $note = json_encode(['sales'=>$request->input('sales'),'PM'=>$request->input('PM'),'SLA'=>$request->input('SLA'),'Transfer'=>$request->input('Transfer'),'type'=>$request->input('type')]);
	    $input_data = $request->except(['sales','PM','SLA','Transfer','type']);
	    
	    
            // if ($orgs->fill($request->input())->save() == true) {
	 
            if($orgs->fill(array_merge($input_data, ['internal_notes'=>$note]))->save()==true){
	    // if($orgs->fill($request->input())->save() == true){
                /* redirect to Index page with Success Message */
                return redirect('organizations')->with('success', Lang::get('lang.organization_updated_successfully'));
            } else {
                /* redirect to Index page with Fails Message */
                return redirect('organizations')->with('fails', Lang::get('lang.organization_can_not_update'));
            }
        } catch (Exception $e) {
            //            dd($e);
            /* redirect to Index page with Fails Message */
            return redirect('organizations')->with('fails', $e->getMessage());
        }
    }

    /**
     * Delete a specified organization from storage.
     *
     * @param type int $id
     *
     * @return type Redirect
     */
    public function destroy($id, Organization $org, User_org $user_org)
    {

        try {
	    
	    $orgs = $org->whereId($id)->first();
	    $user_orgs = $user_org->where('org_id','=',$id)->get();
	    foreach($user_orgs as $user_org){
		$user_org->delete();
	}	    
	    $orgs->delete();
            return redirect('organizations')->with('success', Lang::get('lang.organization_deleted_successfully'));
        } catch (Exception $e) {
            /* redirect to Index page with Fails Message */
            return redirect('organizations')->with('fails', $e->getMessage());
        }
    }

    /**
     * Soring an organization head.
     *
     * @param type $id
     *
     * @return type boolean
     */
    public function Head_Org($id)
    {
        // get the user to make organization head
        $head_user = \Input::get('user');
        // get an instance of the selected organization
        $org_head = Organization::where('id', '=', $id)->first();
        $org_head->head = $head_user;
        // save the user to organization head
        $org_head->save();

        return 1;
    }

    /**
     * get the report of organizations.
     *
     * @param type $id
     * @param type $date111
     * @param type $date122
     *
     * @return type array
     */
    public function orgChartData($id, $date111 = '', $date122 = '')
    {
        $date11 = strtotime($date122);
        $date12 = strtotime($date111);
        if ($date11 && $date12) {
            $date2 = $date12;
            $date1 = $date11;
        } else {
            // generating current date
            $date2 = strtotime(date('Y-m-d'));
            $date3 = date('Y-m-d');
            $format = 'Y-m-d';
            // generating a date range of 1 month
            $date1 = strtotime(date($format, strtotime('-1 month'.$date3)));
        }
        $return = '';
        $last = '';
        for ($i = $date1; $i <= $date2; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);

            $user_orga_relation_id = [];
            $user_orga_relations = User_org::where('org_id', '=', $id)->get();
            foreach ($user_orga_relations as $user_orga_relation) {
                $user_orga_relation_id[] = $user_orga_relation->user_id;
            }
            $created = \DB::table('tickets')->select('created_at')->whereIn('user_id', $user_orga_relation_id)->where('created_at', 'LIKE', '%'.$thisDate.'%')->count();
            $closed = \DB::table('tickets')->select('closed_at')->whereIn('user_id', $user_orga_relation_id)->where('closed_at', 'LIKE', '%'.$thisDate.'%')->count();
            $reopened = \DB::table('tickets')->select('reopened_at')->whereIn('user_id', $user_orga_relation_id)->where('reopened_at', 'LIKE', '%'.$thisDate.'%')->count();

            $value = ['date' => $thisDate, 'open' => $created, 'closed' => $closed, 'reopened' => $reopened];
            $array = array_map('htmlentities', $value);
            $json = html_entity_decode(json_encode($array));
            $return .= $json.',';
        }
        $last = rtrim($return, ',');
        $users = User::whereId($id)->first();

        return '['.$last.']';
    }

    public function getOrgAjax(Request $request)
    {
        $org = new Organization();
        $q = $request->input('term');
        $orgs = $org->where('name', 'LIKE', '%'.$q.'%')
                ->select('name as label', 'id as value')
                ->get()
                ->toJson();

        return $orgs;
    }

    /**
     * This function is used autofill organizations name .
     *
     * @return datatable
     */
    public function organizationAutofill()
    {
        return view('themes.default1.agent.helpdesk.organization.getautocomplete');
    }
}

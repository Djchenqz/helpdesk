@extends('themes.default1.client.layout.client')

@section('title')
My Tickets -
@stop

@section('myorgaticket')
class="active"
@stop

@section('content')
<!-- Main content -->
<div id="content" class="site-content col-md-12">
    <?php
    $open = new App\Model\helpdesk\Ticket\Tickets();
    $close = new App\Model\helpdesk\Ticket\Tickets();
    $user_orga = App\Model\helpdesk\Agent_panel\User_org::where('user_id', '=', Auth::user()->id)->first();
    if ($user_orga != null){
        $user_orga_relation_id = [];
        $user_orga_relations = App\Model\helpdesk\Agent_panel\User_org::where('org_id', '=', $user_orga->org_id)->get();
        foreach ($user_orga_relations as $user_orga_relation) {
            $user_orga_relation_id[] = $user_orga_relation->user_id;
        }
        $open = $open->whereIn('user_id', $user_orga_relation_id)
                    ->where('status', '=', 1)
                    ->orderBy('id', 'DESC')
                    ->paginate(20);
        $close = $close->whereIn('user_id', $user_orga_relation_id)
                    ->whereIn('status', [2, 3])
                    ->orderBy('id', 'DESC')
                    ->paginate(20);
    } else {
        $open = $open->where('user_id', '=', Auth::user()->id)
                    ->where('status', '=', 1)
                    ->orderBy('id', 'DESC')
                    ->paginate(20);
        $close = $close->where('user_id', '=', Auth::user()->id)
                    ->whereIn('status', [2, 3])
                    ->orderBy('id', 'DESC')
                    ->paginate(20);
    }
    ?>
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab" aria-expanded="true">{!! Lang::get('lang.opened') !!} <small class="label bg-orange">{!! $open->total() !!}</small></a></li>
            <li class=""><a href="#tab_2" data-toggle="tab" aria-expanded="false">{!! Lang::get('lang.closed') !!} <small class="label bg-green">{!! $close->total() !!}</small></a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1">
                {!! Form::open(['route'=>'select_all','method'=>'post']) !!}
                <div class=" table-responsive mailbox-messages"  id="refresh1">
                    <p style="display:none;text-align:center; position:fixed; margin-left:37%;margin-top:-80px;" id="show1" class="text-red"><b>Loading...</b></p>
                    <!-- table -->
                    <table class="table table-hover table-striped">
                        <thead>
                        <th></th>
                        <th>
                            {!! Lang::get('lang.subject') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.ticket_id') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.priority') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.last_replier') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.last_activity') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.status') !!}
                        </th>
                        </thead>
                        <tbody id="hello">
                            @foreach ($open  as $ticket )
                            <tr <?php if ($ticket->seen_by == null) { ?> style="color:green;" <?php }
    ?> >
                                <td><input type="checkbox" class="icheckbox_flat-blue" name="select_all[]" value="{{$ticket->id}}"/></td>
                                <?php
                                $title = App\Model\helpdesk\Ticket\Ticket_Thread::where('ticket_id', '=', $ticket->id)->orderBy('id')->first();
                                $string = strip_tags($title->title);
                                if (strlen($string) > 40) {
                                    $stringCut = substr($string, 0, 25);
                                    $string = $stringCut.'....';
                                }
                                $TicketData = App\Model\helpdesk\Ticket\Ticket_Thread::where('ticket_id', '=', $ticket->id)
                                    ->where('user_id', '!=' , null)
                                    ->max('id');
                                $TicketDatarow = App\Model\helpdesk\Ticket\Ticket_Thread::where('id', '=', $TicketData)->first();
                                $LastResponse = App\User::where('id', '=', $TicketDatarow->user_id)->first();
                                if ($LastResponse->role == "user") {
                                    $rep = "#F39C12";
                                    $username = $LastResponse->user_name;
                                } else {
                                    $rep = "#000";
                                    $username = $LastResponse->first_name . " " . $LastResponse->last_name;
                                    if ($LastResponse->first_name == null || $LastResponse->last_name == null) {
                                        $username = $LastResponse->user_name;
                                    }
                                }
                                $titles = App\Model\helpdesk\Ticket\Ticket_Thread::where('ticket_id', '=', $ticket->id)->where('is_internal', '=', 0)->get();
                                $count = count($titles);
                                foreach ($titles as $title) {
                                    $title = $title;
                                }
                                ?>
                                <td class="mailbox-name"><a href="{!! URL('check_ticket',[Crypt::encrypt($ticket->id)]) !!}" title="{!! $title->title !!}">{{$string}}   </a> ({!! $count!!}) <i class="fa fa-comment"></i></td>
                                <td class="mailbox-Id">#{!! $ticket->ticket_number !!}</td>
                                <?php $priority = App\Model\helpdesk\Ticket\Ticket_Priority::where('priority_id', '=', $ticket->priority_id)->first(); ?>
                                <td class="mailbox-priority"><spam class="btn btn-{{$priority->priority_color}} btn-xs">{{$priority->priority}}</spam></td>

                        <td class="mailbox-last-reply" style="color: {!! $rep !!}">{!! $username !!}</td>
                        <td class="mailbox-last-activity">{!! $title->updated_at !!}</td>
                        <?php $status = App\Model\helpdesk\Ticket\Ticket_Status::where('id', '=', $ticket->status)->first(); ?>
                        <td class="mailbox-date">{!! $status->name !!}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table><!-- /.table -->
                    <div class="pull-right">
                        <?php echo $open->setPath(url('mytickets'))->render(); ?>&nbsp;
                    </div>
                </div><!-- /.mail-box-messages -->
                {!! Form::close() !!}
            </div><!-- /.box-body -->
            {{-- /.tab_1 --}}
            <div class="tab-pane" id="tab_2">
                {!! Form::open(['route'=>'select_all','method'=>'post']) !!}
                <div class=" table-responsive mailbox-messages" id="refresh2">
                    <p style="display:none;text-align:center; position:fixed; margin-left:40%;margin-top:-70px;" id="show2" class="text-red"><b>Loading...</b></p>
                    <!-- table -->
                    <table class="table table-hover table-striped">
                        <thead>
                        <th></th>
                        <th>
                            {!! Lang::get('lang.subject') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.ticket_id') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.priority') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.last_replier') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.last_activity') !!}
                        </th>
                        <th>
                            {!! Lang::get('lang.status') !!}
                        </th>
                        </thead>
                        <tbody id="hello">
                            @foreach ($close  as $ticket )
                            <tr <?php if ($ticket->seen_by == null) { ?> style="color:green;" <?php }
                        ?> >
                                <td><input type="checkbox" class="icheckbox_flat-blue" name="select_all[]" value="{{$ticket->id}}"/></td>
                                <?php
                                $title = App\Model\helpdesk\Ticket\Ticket_Thread::where('ticket_id', '=', $ticket->id)->first();
                                $string = strip_tags($title->title);
                                if (strlen($string) > 40) {
                                    $stringCut = substr($string, 0, 40);
                                    $string = substr($stringCut, 0, strrpos($stringCut, ' ')) . ' ...';
                                }
                                $TicketData = App\Model\helpdesk\Ticket\Ticket_Thread::where('ticket_id', '=', $ticket->id)->max('id');
                                $TicketDatarow = App\Model\helpdesk\Ticket\Ticket_Thread::where('id', '=', $TicketData)->first();
                                $LastResponse = App\User::where('id', '=', $TicketDatarow->user_id)->first();
                                if ($LastResponse->role == "user") {
                                    $rep = "#F39C12";
                                    $username = $LastResponse->user_name;
                                } else {
                                    $rep = "#000";
                                    $username = $LastResponse->first_name . " " . $LastResponse->last_name;
                                    if ($LastResponse->first_name == null || $LastResponse->last_name == null) {
                                        $username = $LastResponse->user_name;
                                    }
                                }
                                $titles = App\Model\helpdesk\Ticket\Ticket_Thread::where('ticket_id', '=', $ticket->id)->where("is_internal", "=", 0)->get();
                                $count = count($titles);
                                foreach ($titles as $title) {
                                    $title = $title;
                                }
                                ?>
                                <td class="mailbox-name"><a href="{!! URL('check_ticket',[Crypt::encrypt($ticket->id)]) !!}" title="{!! $title->title !!}">{{$string}}   </a> ({!! $count!!}) <i class="fa fa-comment"></i></td>
                                <td class="mailbox-Id">#{!! $ticket->ticket_number !!}</td>
                                <?php $priority = App\Model\helpdesk\Ticket\Ticket_Priority::where('priority_id', '=', $ticket->priority_id)->first(); ?>
                                <td class="mailbox-priority"><spam class="btn btn-{{$priority->priority_color}} btn-xs">{{$priority->priority}}</spam></td>
                        <td class="mailbox-last-reply" style="color: {!! $rep !!}">{!! $username !!}</td>
                        <td class="mailbox-last-activity">{!! $title->updated_at !!}</td>
                        <?php $status = App\Model\helpdesk\Ticket\Ticket_Status::where('id', '=', $ticket->status)->first(); ?>
                        <td class="mailbox-date">{!! $status->name !!}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table><!-- /.table -->
                    <div class="pull-right">
                        <?php echo $close->setPath(url('mytickets'))->render(); ?>&nbsp;
                    </div>
                </div><!-- /.mail-box-messages -->
                {!! Form::close() !!}
            </div>
        </div><!-- /. box -->
    </div>
</div>
<script>
    $(function() {
        //Enable check and uncheck all functionality
        $(".checkbox-toggle").click(function() {
            var clicks = $(this).data('clicks');
            if (clicks) {
                //Uncheck all checkboxes
                $(".mailbox-messages input[type='checkbox']").iCheck("uncheck");
                $(".fa", this).removeClass("fa-check-square-o").addClass('fa-square-o');
            } else {
                //Check all checkboxes
                $(".mailbox-messages input[type='checkbox']").iCheck("check");
                $(".fa", this).removeClass("fa-square-o").addClass('fa-check-square-o');
            }
            $(this).data("clicks", !clicks);
        });
    });

    $(function() {
        // Enable check and uncheck all functionality
        $(".checkbox-toggle").click(function() {
            var clicks = $(this).data('clicks');
            if (clicks) {
                //Uncheck all checkboxes
                $("input[type='checkbox']", ".mailbox-messages").iCheck("uncheck");
            } else {
                //Check all checkboxes
                $("input[type='checkbox']", ".mailbox-messages").iCheck("check");
            }
            $(this).data("clicks", !clicks);
        });
    });

    $(document).ready(function() { /// Wait till page is loaded
        $('#click1').click(function() {
            $('#refresh1').load('mytickets #refresh1');
            $('#refresh21').load('mytickets #refresh21');
            $("#show1").show();
        });
    });

    $(document).ready(function() { /// Wait till page is loaded
        $('#click2').click(function() {
            $('#refresh2').load('mytickets #refresh2');
            $('#refresh22').load('mytickets #refresh22');
            $("#show2").show();
        });
    });

</script>
@stop
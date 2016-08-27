{include file="user/layout/themes/{$USER_THEME_FOLDER}/header.tpl"  name=""}
<div id="span_js_messages" style="display:none;">
    <span id="error_msg1">{lang('please_enter_your_company_name')}</span>        
    <span id="row_msg">{lang('rows')}</span>
    <span id="show_msg">{lang('shows')}</span>
    <span id="error_msg">{lang('please_enter_your_company_name')}</span>
    <span id="confirm_msg">{lang('sure_you_want_to_delete_this_feedback_there_is_no_undo')}</span>
</div>
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-external-link-square"></i>
                <div class="panel-tools">
                    <a class="btn btn-xs btn-link panel-collapse collapses" href="#">
                    </a>
                    <a class="btn btn-xs btn-link panel-refresh" href="#">
                        <i class="fa fa-refresh"></i>
                    </a>
                    <a class="btn btn-xs btn-link panel-expand" href="#">
                        <i class="fa fa-resize-full"></i>
                    </a>
                </div>  {lang('users_referal_details')}
            </div>
            <div class="panel-body">

                <table class="table table-striped table-bordered table-hover table-full-width" id="">

                    <thead>
                        <tr class="th" align="center">
                            <th>{lang('sl_no')}</th>
                            <th>{lang('user_name')}</th>
                            <th>{lang('full_name')}</th>
                            <th>{lang('joinig_date')}</th>
                            <th> {lang('email')}</th>
                            <th>{lang('country')}</th>
                        </tr>
                    </thead>
                    {if $count>0}
                        <tbody>
                            {assign var="i" value="0"}
                            {assign var="class" value=""}
                            {foreach from=$arr item=v}
                                {if $i%2==0}
                                    {$class='tr1'}
                                {else}
                                    {$class='tr2'}
                                {/if}
                                <tr class="{$class}" align="center" >
                                    <td>{counter}</td>
                                    <td>{$v.user_name}</td>
                                    <td>{$v.name}</td>
                                    <td>{$v.join_date}</td>
                                    <td> {$v.email}</td>
                                    <td>{$v.country}</td>
                                </tr>
                                {$i=$i+1}
                            {/foreach}                        
                        </tbody>
                    {else}                   
                        <tbody>
                            <tr><td colspan="12" align="center"><h4>{lang('no_referels')}</h4></td></tr>
                        </tbody> 
                    {/if}
                </table>
                {$result_per_page}
            </div>
        </div>
    </div>
</div>
{include file="user/layout/themes/{$USER_THEME_FOLDER}/footer.tpl" title="Example Smarty Page" name=""}
<script>
    jQuery(document).ready(function() {
        Main.init();
        TableData.init();
        ValidateUser.init();
        DateTimePicker.init();
    });
</script>

{include file="user/layout/themes/{$USER_THEME_FOLDER}/page_footer.tpl" title="Example Smarty Page" name=""}
<?php

namespace Modules\ApiExtender\Http\Controllers;

use App\Conversation;
use App\Customer;
use App\Thread;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Reports\Http\Controllers\ReportsController;

class ReportApiController extends ReportsController
{



    /**
     * Ajax controller.
     */
    public function publicReport(Request $request, $report_name)
    {
        $response = [
            'status' => 'error',
            'msg'    => '', // this is error message
        ];

        switch (strtolower($report_name)) {
            case \Reports::REPORT_CONVERSATIONS:
                $data = $this->getReportDataConversations($request);
                $response['data'] = $data;
                $response['status'] = 'success';
                break;

            case \Reports::REPORT_PRODUCTIVITY:
                $data = $this->getReportDataProductivity($request);
                $response['data'] = $data;
                $response['status'] = 'success';
                break;
                        
            case \Reports::REPORT_SATISFACTION:
                $data = $this->getReportSatisfaction($request);
                $response['data'] = $data;
                $response['status'] = 'success';
                break;

            case \Reports::REPORT_TIME:
                $data = $this->getReportTime($request);
                $response['data'] = $data;
                $response['status'] = 'success';
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        if ($response['status'] == 'error' && empty($response['msg'])) {
            $response['msg'] = 'Unknown error occured';
        }

        return \Response::json($response);
    }

    

    public function getReportTime($request)
    {
        $data = parent::getReportTime($request);
        
        return $data;
    }

    public function strtimeToMinutes($timeString)
    {
        $days = 0;
        $hours = 0;
        $minutes = 0;

        // Split the string by spaces to separate each part
        $parts = explode(' ', $timeString);

        // Iterate over the parts to extract days, hours, and minutes
        for ($i = 0; $i < count($parts); $i += 2) {
            $value = intval($parts[$i]);
            $unit = $parts[$i + 1];

            if (strpos($unit, 'd') !== false) {
                $days = $value;
            } elseif (strpos($unit, 'h') !== false) {
                $hours = $value;
            } elseif (strpos($unit, 'min') !== false) {
                $minutes = $value;
            }
        }

        // Calculate the total minutes
        $totalMinutes = ($days * 24 * 60) + ($hours * 60) + $minutes;

        return $totalMinutes;
    }

    public function buildTimeTable($table, $meta_name, $metas, $metas_prev, $user_id = false)
    {
        $table = parent::buildTimeTable($table, $meta_name, $metas, $metas_prev, $user_id);
        // Add average.
        try {
            $table[-2] = $this->strtimeToMinutes($table[-1]);
        } catch (\Throwable $th) {
            $table[-2] = 60;
        }
        return $table;
    }



    

    public function applyFilter($query, $request, $prev = false, $date_field = 'conversations.created_at', $date_field_to = '')
    {
        $from = $request->filters['from'];
        $to = $request->filters['to'];

        $user = User::find($request->filters['auth_user'] ?? 1);

        if (!$date_field_to) {
            $date_field_to = $date_field;
        }

        if ($prev) {
            if ($from && $to) {
                $from_carbon = Carbon::parse($from);
                $to_carbon = Carbon::parse($to);

                $days = $from_carbon->diffInDays($to_carbon);

                if ($days) {
                    $from = $from_carbon->subDays($days)->format('Y-m-d');
                    $to = $to_carbon->subDays($days)->format('Y-m-d');
                }
            }
        }
        
        $query_table_name = $query->getModel()->getTable();
        switch ($query_table_name) {
            case 'threads':
                $query_table_user_id_field = 'threads.created_by_user_id';
                break;
            
            default:
                $query_table_user_id_field = '';
                break;
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        if (
            !empty($request->filters['user']) && 
            'threads' === $query_table_name
        ) {
            $query->where('threads.created_by_user_id', $request->filters['user']);
        }

        if ( isset($request->filters['include_bot']) && ! (bool) $request->filters['include_bot']) {
            
            if ( !empty(env('FREESCOUT_BOT_USER')) ) {
                $exclude_bots = array_filter( explode( ',', env('FREESCOUT_BOT_USERS') ) );
            } else {
                $exclude_bots = [4,5];
            }

            if ( !empty($query_table_user_id_field) && count( $exclude_bots ) ) {
                $query->whereNotIn( $query_table_user_id_field, $exclude_bots );
            }
        }

        if (!empty($from)) {
            $query->where($date_field, '>=', date('Y-m-d 00:00:00', strtotime($from)));
        }
        if (!empty($to)) {
            $query->where($date_field_to, '<=', date('Y-m-d 23:59:59', strtotime($to)));
        }
        if (!empty($request->filters['type'])) {
            $query->where('conversations.type', $request->filters['type']);
        }
        if (!empty($request->filters['mailbox'])) {
            $query->where('conversations.mailbox_id', $request->filters['mailbox']);
        } elseif (!$user->isAdmin()) {
            $mailbox_ids = $user->mailboxesCanView(true)->pluck('id');
            if (count($mailbox_ids)) {
                $query->whereIn('conversations.mailbox_id', $mailbox_ids);
            }
        }
        if (!empty($request->filters['tag']) && \Module::isActive('tags')) {
            if (!strstr($query->toSql(), 'conversation_tag')) {
                $query->leftJoin('conversation_tag', function ($join) {
                    $join->on('conversations.id', '=', 'conversation_tag.conversation_id');
                });
            }
            $query->where('conversation_tag.tag_id', $request->filters['tag']);
        }

        // Custom fields.
        if (!empty($request->filters['custom_field']) && \Module::isActive('customfields')) {
            $custom_fields = \Reports::getCustomFieldFilters();

            if (count($custom_fields)) {
                foreach ($custom_fields as $custom_field) {
                    if (!empty($request->filters['custom_field'][$custom_field->id])) {
                        $join_alias = 'ccf'.$custom_field->id;
                        $value = $request->filters['custom_field'][$custom_field->id];

                        $query->join('conversation_custom_field as '.$join_alias, function ($join) use ($custom_field, $value, $join_alias) {
                            $join->on('conversations.id', '=', $join_alias.'.conversation_id');
                            $join->where($join_alias.'.custom_field_id', $custom_field->id);
                            if ($custom_field->type == \Modules\CustomFields\Entities\CustomField::TYPE_MULTI_LINE) {
                                $join->where($join_alias.'.value', 'like', '%'.$value.'%');
                            } else {
                                $join->where($join_alias.'.value', $value);
                            }
                        });
                    }
                }
            }
        }
    }
}

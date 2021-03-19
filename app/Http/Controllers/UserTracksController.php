<?php

namespace App\Http\Controllers;


use http\Env\Response;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Track;
use App\Models\Project;
use App\Models\Task;
use phpDocumentor\Reflection\Types\This;
use Illuminate\Support\Facades\Validator;
class UserTracksController extends Controller
{

    public function getUserTracks(Request $req, $id) {
        $rules = [
            "count" => "required",
            "offset" => "required",
            "before_send_time" => "required|date_format:Y-m-d H:i:s",
            "since_send_time" => "required|date_format:Y-m-d H:i:s",
        ];
        $user = User::find($id);
        $validator = Validator::make($req->all(), $rules);
        $fails = self::validateQueries($user, $validator);

        if($fails) {
            return response()->json(["message" => "Validation failed", "errors" => $fails], 400);
        }


        $tracks = $user->tracks()->skip($req->offset)->take($req->count)->whereBetween('created_at', [$req->since_send_time, $req->before_send_time])->with('task.project.client')->get();
//        dd($tracks);

        $response = [];
        foreach ($tracks as $keyTrack => $track) {
            $response[] = [
                "Track" => [
                    "id" => $track->id,
                    "duration" => $track->duration,
                    "start" => strtotime($track->start),
                    "end" => strtotime($track->end),
                    "description" => $track->description
                ],
                "User" => [
                    "id" => $track->user->id,
                    "email" => $track->user->email,
                    "first_name" => $track->user->first_name,
                    "last_name" => $track->user->last_name
                ],
                "Project" => [
                    "id" => $track->task->project->id,
                    "name" => $track->task->project->name
                ],
                "Client" => [
                    "id" => $track->task->project->client->id,
                    "name" => $track->task->project->client->name
                ]
            ];
        }

        return response()->json($response, 200);

    }

    public function createTrack(Request $req) {
        $rules = [
            "user_id" => "required|integer",
            "project_id" => "required|integer",
            "task_id" => "required|integer",
            "start_time" => "required|date_format:Y-m-d H:i:s",
            "stop_time" => "after_or_equal:start_time|date_format:Y-m-d H:i:s"
        ];
        $user = User::find($req->user_id);
        $validator = Validator::make($req->all(), $rules);
        $fails = self::validateQueries($user, $validator);

        if(is_null(Project::find($req->project_id)))
            $fails[] = ["field" => "project_id" , "message" => "Project cannot be found"];

        if(is_null(Task::find($req->task_id)))
            $fails[] = ["field" => "task_id" , "message" => "Task cannot be found"];

        if($fails) {
            return response()->json(["message" => "Validation failed", "errors" => $fails], 400);
        }

        $track = new Track;

        $track->start = $req->start_time;
        if(isset($req->stop_time)) {
            $track->end = $req->stop_time;
            $track->duration = strtotime($req->stop_time) - strtotime($req->start_time);
        }
        if(isset($req->description)) {
            $track->description = $req->description;
        }
        $track->user_id = $req->user_id;
        $track->task_id = $req->task_id;
        $track->created_at = date("Y-m-d H:i:s");

//        dump($track);
        $track->save();
        return response()->json(["message" => "success", "track" => $track], 201);

    }

    private static function validateQueries($user, $validator) {
        $fails = false;
        if($user === null) {
            $fails[] = ["field" => "user", "message" => "User cannot be found"];
        }
        if($validator->fails()) {
            $validationErrors = $validator->errors();
            $keys = $validationErrors->keys();
            $messages = $validationErrors->all();
            for($i = 0; $i < count($keys); $i++) {
                $fails[] = ["field" => $keys[$i], "message" => $messages[$i]];
            }
        }
        return $fails ? $fails : false;
    }

}

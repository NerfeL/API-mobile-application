# Laravel API-mobile-application


1) Get a list of tracks by specific user
    GET /tracks/user/{user_id}

        count
        offset
        before_send_time
        since_send_time

2) Create a new track
    POST /tracks

        user_id
        project_id
        task_id
        description*
        start_time
        stop_time
<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/tasks', [TaskController::class, 'getTasks']);



Route::get('/users', [UserController::class, 'getUsers']);
Route::post('/users', [UserController::class, 'createUser']);

Route::get('/users/age-range', [UserController::class, 'getUsersByAgeRange']);
Route::get('/users/min-balance', [UserController::class, 'getUsersWithMinBalance']);
Route::get('/users/search', [UserController::class, 'searchUsers']);
Route::get('/users/sorted', [UserController::class, 'getSortedUsers']);


Route::get('/user-tickets', function () {
    $results = DB::select("
        SELECT 
            users.id AS user_id, 
            users.name, 
            users.email, 
            tickets.ticket_id,  -- Fixed this (was tickets.id)
            tickets.price, 
            tickets.departure, 
            tickets.destination, 
            tickets.travel_date, 
            tickets.is_expired
        FROM users 
        JOIN tickets ON users.id = tickets.user_id
    ");

    return response()->json($results);
});


Route::get('/users-ticket-count', function () {
    $results = DB::select("
        SELECT 
            users.id AS user_id, 
            users.name, 
            users.email, 
            COUNT(tickets.ticket_id) AS total_tickets
        FROM users
        LEFT JOIN tickets ON users.id = tickets.user_id
        GROUP BY users.id, users.name, users.email
    ");

    return response()->json($results);
});


// only the users that have tickets
Route::get('/users-with-tickets', function () {
    $results = DB::select("
        SELECT 
            users.id AS user_id, 
            users.name, 
            users.email,
            tickets.ticket_id,
            tickets.price,
            tickets.departure,
            tickets.destination,
            tickets.travel_date
        FROM users
        INNER JOIN tickets ON users.id = tickets.user_id
        ORDER BY users.id, tickets.travel_date DESC
    ");

    return response()->json($results);
});

Route::get('/top-spender', function () {
    $results = DB::select("
        SELECT 
            users.id AS user_id, 
            users.name, 
            users.email,
            SUM(tickets.price) AS total_spent
        FROM users
        JOIN tickets ON users.id = tickets.user_id
        GROUP BY users.id, users.name, users.email
        ORDER BY total_spent DESC
        LIMIT 1
    ");

    return response()->json($results);
});

Route::get('/users-without-tickets', function () {
    $results = DB::select("
        SELECT 
            users.id AS user_id, 
            users.name, 
            users.email
        FROM users
        LEFT JOIN tickets ON users.id = tickets.user_id
        WHERE tickets.ticket_id IS NULL
    ");

    return response()->json($results);
});



Route::get('/popular-destination', function () {
    $results = DB::select("
        SELECT 
            destination, 
            COUNT(ticket_id) AS total_tickets
        FROM tickets
        GROUP BY destination
        ORDER BY total_tickets DESC
        LIMIT 1
    ");

    return response()->json($results);
});


//! doesnt work
Route::get('/above-average-spenders', function () {
    $results = DB::select("
        SELECT 
            users.id AS user_id, 
            users.name, 
            users.email, 
            total_spent
        FROM (
            SELECT 
                users.id, 
                users.name, 
                users.email, 
                SUM(tickets.price) AS total_spent
            FROM users
            JOIN tickets ON users.id = tickets.user_id
            GROUP BY users.id, users.name, users.email
        ) AS user_spending
        WHERE total_spent > (
            SELECT AVG(price) FROM tickets
        )
        ORDER BY total_spent DESC
    ");

    return response()->json($results);
});

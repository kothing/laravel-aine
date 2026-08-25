<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    
    /**
     * Update user's name
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->name = $request->name;
        $user->save();
        return response()->json(['success' => true]);
    }

    /**
     * Update user's email
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateEmail(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            // Prevent a hijacked session from re-binding the account email.
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
        ]);

        $user->email = $request->email;
        $user->save();
        return response()->json(['success' => true]);
    }

    /**
     * Update user's password
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {   
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
            // Prevent a hijacked session from changing the password.
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
        ]);

        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json(['success' => true]);
    }

    /**
     * Update the user's profile (name / email / password) in one request.
     *
     * Only fields that are actually provided will be updated.
     * Changing the e-mail or password requires the current password.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|required|string|min:8|confirmed',
        ];

        // Changing the e-mail or password requires the current password.
        if (($request->filled('email') && $request->email !== $user->email)
            || $request->filled('password')) {
            $rules['current_password'] = ['required', function ($attribute, $value, $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }];
        }

        $request->validate($rules);

        if ($request->filled('name') && $request->name !== $user->name) {
            $user->name = $request->name;
        }
        if ($request->filled('email') && $request->email !== $user->email) {
            $user->email = $request->email;
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();
        return response()->json(['success' => true]);
    }
}

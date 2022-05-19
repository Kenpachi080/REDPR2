<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ChangeRequest;
use App\Http\Requests\CodeRequest;
use App\Http\Requests\ForgotRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RebootPasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Mail\ForgotMail;
use Illuminate\Http\Request;
use App\Mail\RegisterMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/auth/register",
     * summary="Регистрация",
     * description="Регистрация",
     * operationId="authRegister",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Регистрация",
     *    @OA\JsonContent(
     *       required={"phone, password, email"},
     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
     *       @OA\Property(property="password", type="string", format="string", example="123"),
     *       @OA\Property(property="email", type="string", format="string", example="testemail@mail.ru"),
     *    ),
     * ),
     *
     * @OA\Response(
     *    response=201,
     *    description="Возврощается полная информация про пользователя, и его токен для дальнейшей работы с юзером",
     *    @OA\JsonContent(
     *       type="object",
     *             @OA\Property(
     *                property="user",
     *                type="object",
     *               example={
     *                  "name": "+7712308",
     *                   "email": "test1231email@mail.ru",
     *                   "updated_at": "2022-04-20T19:53:52.000000Z",
     *                   "created_at": "2022-04-20T19:53:52.000000Z",
     *                   "id": 10,
     *                   "api_token": "18|TuQoXj84z5IxclUeRK89bSS4839sQfJ8KsQRVRVO"
     *                  }
     *              ),
     *     @OA\Property(
     *                property="token",
     *                type="string",
     *               example="18|TuQoXj84z5IxclUeRK89bSS4839sQfJ8KsQRVRVO",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        $validateEmail = User::where('email', '=', $request->email)->first();
        if ($validateEmail) {
            return response(['Exception' => 'Эта электронная почта уже используется.'], 409);
        }
        $validatePhone = User::where('telephone', '=', $request->phone)->first();
        if ($validatePhone) {
            return response(['Exception' => 'Этот номер телефона уже используется.'], 409);
        }
        $phone = $request->phone;
        $user = User::create([
            'name' => $phone,
            'telephone' => $phone,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        $token = $user->createToken('myapptoken')->plainTextToken;
        $user->api_token = $token;
        $user->save();
        $response = [
            'user' => $user,
            'token' => $token,
        ];
        return response($response, 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/login",
     * summary="Авторизация",
     * description="Авторизация по АПИ токену",
     * operationId="authLogin",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"phone, password"},
     *       @OA\Property(property="phone", type="string", format="string", example="+7708"),
     *       @OA\Property(property="password", type="string", format="string", example="123"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="Возврощается полная информация про пользователя, и его токен для дальнейшей работы с юзером",
     *    @OA\JsonContent(
     *       type="object",
     *             @OA\Property(
     *                property="user",
     *                type="object",
     *               example={
     *                  "id": 8,
     *                     "role_id": 2,
     *                     "name": "+7708",
     *                     "email": "testemail@mail.ru",
     *                     "avatar": "users/default.png",
     *                     "email_verified_at": null,
     *                     "settings": null,
     *                     "created_at": "2022-04-20T19:31:30.000000Z",
     *                     "updated_at": "2022-04-20T19:58:44.000000Z",
     *                     "fio": null,
     *                   "telephone": null,
     *                     "birthday": null,
     *                     "address": null,
     *                     "api_token": "FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"
     *                  }
     *              ),
     *     @OA\Property(
     *                property="token",
     *                type="string",
     *               example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('name', '=', $request->phone)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Неверный пароль'
            ], 401);
        }

        $token = Str::random(60);
        $user->api_token = $token;
        $user->save();
        $response = [
            'user' => $user,
            'token' => $token
        ];
        return response($response, 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/rebootpassword",
     * summary="Поменять пароль",
     * description="Поменять пароль",
     * operationId="rebootpassword",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"oldpassword, newpassword"},
     *       @OA\Property(property="oldpassword", type="string", format="string", example="123"),
     *       @OA\Property(property="newspassword", type="string", format="string", example="321"),
     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="CallBack с статусом",
     *    @OA\JsonContent(
     *       type="object",
     *     @OA\Property(
     *                property="message",
     *                type="string",
     *               example="Пароль был успешно изменен",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */
    public function rebootpassword(RebootPasswordRequest $request)
    {
        $user = User::where('id', '=', Auth::id())->first();
        if (!$user || !Hash::check($request->oldpassword, $user->password)) {
            return response([
                'message' => "Неверный старый пароль"
            ], 401);
        }
        $user->password = bcrypt($request->newspassword);
        $user->save();
        return response([
            'message' => 'Пароль был успешно заменён!'
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/change",
     * summary="Поменять данные клиента",
     * description="Поменять данные клиента",
     * operationId="authChange",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"fio, email, telephone, address"},
     *       @OA\Property(property="fio", type="string", format="string", example="123"),
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="telephone", type="string", format="string", example="321"),
     *       @OA\Property(property="address", type="string", format="string", example="321"),
     *       @OA\Property(property="birthday", type="date", format="date", example="23.10.2002"),
     *       @OA\Property(property="api_token", type="string", format="string", example="FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM"),
     *  ),
     * ),
     * @OA\Response(
     *    response=201,
     *    description="CallBack с статусом",
     *    @OA\JsonContent(
     *       type="object",
     *             @OA\Property(
     *                property="user",
     *                type="object",
     *               example={
     *                      "id": 8,
     *                      "api_token": "FKOhXAr6Xhx2e6fMdaKZbTOCxCBwLuJDO3j8fYjRoDG9XoAYKQUSPzayU4BM",
     *                      "fio": "123",
     *                      "email": "321",
     *                      "telephone": "321",
     *                      "address": "321",
     *                      "birthday": "23.10.2002",
     *                  }
     *              ),
     *     @OA\Property(
     *                property="message",
     *                type="string",
     *               example="Данные успешно были изменены",
     *              ),
     *     ),
     *        )
     *     )
     * )
     */
    public function change(ChangeRequest $request)
    {

        $user = User::where('id', '=', Auth::id())->select('id', 'api_token', 'fio', 'email', 'telephone', 'address')->first();
        if (!$user) {
            return response([
                'message' => 'Пользователь не был найден'
            ], 401);
        }
        $user->fio = $request->fio;
        $user->email = $request->email;
        $user->telephone = $request->telephone;
        $user->address = $request->address;
        $user->birthday = $request->birthday;
        $user->save();
        return response([
            'message' => 'Данные успешно были изменены',
            'user' => $user
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/auth/forgot",
     * summary="Забыл пароль",
     * description="забыл пароль",
     * operationId="forgot",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"email, phone"},
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="phone", type="string", format="string", example="321"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="На почту был отправлен код",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function forgot(ForgotRequest $request)
    {
        if ($request->email) {
            $user = User::where('email', '=', $request->email)->first();
        } else if ($request->telephone) {
            $user = User::where('telephone', '=', $request->telephone)->first();
        } else {
            $user = null;
        }
        if ($user == null || !$user) {
            return response('Не найден пользователь', 404);
        }
        $code = Str::random(6);
        $user->code = $code;
        $user->save();
        Mail::to($user->email)->send(new ForgotMail($code));
        return response('На почту был отпрввлен код', 200);
    }

    /**
     * @OA\Post(
     * path="/api/auth/code",
     * summary="Подтвердить код",
     * description="Подтвердить код",
     * operationId="code",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"email, phone, code"},
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="phone", type="string", format="string", example="321"),
     *       @OA\Property(property="code", type="string", format="string", example=""),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Правильный код",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function code(CodeRequest $request)
    {
        if ($request->email) {
            $user = User::where('email', '=', $request->email)->first();
        } else if ($request->phone) {
            $user = User::where('telephone', '=', $request->phone)->first();
        }
        if ($user != null) {
            if ($user->code == $request->code) {
                return response(['message' => 'Правильный код'], 200);
            } else {
                return response(['message' => 'Не правильный код'], 404);
            }
        }
    }

    /**
     * @OA\Post(
     * path="/api/auth/changePassword",
     * summary="Помменять пароль",
     * description="Помменять пароль",
     * operationId="changePassword",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"password, email, phone, address"},
     *       @OA\Property(property="password", type="string", format="string", example="123"),
     *       @OA\Property(property="email", type="string", format="string", example="321"),
     *       @OA\Property(property="phone", type="string", format="string", example="321"),
     *       @OA\Property(property="code", type="string", format="string", example=""),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с товаром",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        if ($request->email) {
            $user = User::where('email', '=', $request->email)->first();
        } else if ($request->phone) {
            $user = User::where('telephone', '=', $request->phone)->first();
        }
        if (!$user->code == $request->code) {
            return response(['message' => 'Не правильный код'], 404);
        }
        $user->password = bcrypt($request->password);
        $user->code = '';
        $user->save();
        return response('Пароль успешно заменен', 200);
    }

    /**
     * @OA\Post(
     * path="/api/auth/view",
     * summary="Посмотреть данные",
     * description="Посмотреть данные",
     * operationId="viewauth",
     * tags={"auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Апи Токен",
     *    @OA\JsonContent(
     *       required={"api_token"},
     *       @OA\Property(property="api_token", type="string", format="string", example="6WxjM0XOruMPWPnJKEAPHNIMwNpe0bAU7iGWswoKrQDuXC5MNUmuJh1Y4GuG"),
     *  ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="CallBack с данными",
     *    @OA\JsonContent(
     *       type="object",
     *        )
     *     )
     * )
     */
    public function view()
    {
        $user = User::where('id', '=', Auth::id())
            ->select('name', 'email', 'created_at', 'fio', 'telephone', 'birthday', 'address')
            ->first();
        return $user;
    }

    private function date_normalise($date, $format)
    {
//        $res['date'] = date($format, $date);
        $res = date($format, $date);
        return $res->format($format);
    }
}

?>

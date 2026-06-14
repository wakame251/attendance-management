<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一般ユーザー会員登録：名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_register_name_is_required()
    {
        $response = $this->post('/register', [
            'user_name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'user_name' => 'お名前を入力してください',
        ]);
    }

    /**
     * 一般ユーザー会員登録：メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_register_email_is_required()
    {
        $response = $this->post('/register', [
            'user_name' => 'テスト太郎',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 一般ユーザー会員登録：パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_register_password_must_be_at_least_8_characters()
    {
        $response = $this->post('/register', [
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * 一般ユーザー会員登録：確認用パスワードと一致しない場合、バリデーションメッセージが表示される
     */
    public function test_register_password_confirmation_must_match()
    {
        $response = $this->post('/register', [
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * 一般ユーザー会員登録：パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_register_password_is_required()
    {
        $response = $this->post('/register', [
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 一般ユーザー会員登録：正常に登録できる
     */
    public function test_user_can_register()
    {
        $response = $this->post('/register', [
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * 一般ユーザーログイン：メールアドレス未入力
     */
    public function test_user_login_email_is_required()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 一般ユーザーログイン：パスワード未入力
     */
    public function test_user_login_password_is_required()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 一般ユーザーログイン：登録内容と一致しない
     */
    public function test_user_login_fails_with_invalid_credentials()
    {
        User::create([
            'user_name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * 全ての項目が正しく入力されている場合、
     * 会員情報が登録され、メール認証誘導画面に遷移する
     */
    public function test_user_can_register_and_redirect_to_verification_notice(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'user_name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 認証前は、プロフィール設定画面ではなく認証誘導画面へ行く前提
        $response->assertRedirect('/email/verify');

        $this->assertDatabaseHas('users', [
            'user_name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * 会員登録後、認証メールが送信される
     */
    public function test_verification_email_is_sent_after_registration(): void
    {
        Notification::fake();

        $this->post('/register', [
            'user_name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * メール認証誘導画面が表示される
     */
    public function test_verification_notice_screen_can_be_displayed(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    /**
     * 認証メール内の認証URLへアクセスすると、メール認証が完了し、
     * 勤怠登録画面に遷移する
     */
    public function test_user_can_verify_email_and_redirect_to_attendance(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance');

        $user->refresh();

        $this->assertTrue($user->hasVerifiedEmail());
    }

    /**
     * 管理者ログイン：メールアドレス未入力
     */
    public function test_admin_login_email_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 管理者ログイン：パスワード未入力
     */
    public function test_admin_login_password_is_required()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 管理者ログイン：登録内容と一致しない
     */
    public function test_admin_login_fails_with_invalid_credentials()
    {
        Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

}

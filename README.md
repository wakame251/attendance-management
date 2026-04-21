# プロジェクトについて

---

## アプリケーション名

### coachtech 勤怠管理アプリ

## 概要

### ユーザーの勤怠と管理をするためのアプリを開発

## 主な機能

###　一般ユーザー（スタッフ）

- 会員登録 / ログイン機能（Laravel Fortify）
- 勤怠の打刻機能（出退勤時刻や休憩の入り・戻り時刻の登録）
- 勤怠一覧の表示
- 勤怠詳細の表示
- 勤怠の修正および申請機能
- 勤怠修正申請の一覧表示

###　管理者

- ログイン機能
- スタッフの勤怠一覧の表示
- スタッフの勤怠詳細の表示
- スタッフの勤怠修正機能
- スタッフ一覧の表示
- スタッフ別の勤怠一覧の表示
- スタッフの勤怠修正申請一覧の表示
- スタッフの勤怠修正承認機能

# 環境構築

---

## Dockerビルド

### ①git clone git@github.com:wakame251/free-market.git

### ②cd attendance-management

### ③docker-compose up -d --build

## Laravel環境構築

### ①docker-compose exec php bash

### ②composer install

### ③cp .env.example .env

_.envを作成後、以下の設定を確認・追記してください。_

DB_HOST=mysql

DB_DATABASE=laravel_db

DB_USERNAME=laravel_user

DB_PASSWORD=laravel_pass

MAIL_MAILER=smtp

MAIL_HOST=mailhog

MAIL_PORT=1025

MAIL_USERNAME=null

MAIL_PASSWORD=null

MAIL_ENCRYPTION=null

MAIL_FROM_ADDRESS=no-reply@example.com

MAIL_FROM_NAME="COACHTECH"

### ④ php artisan key:generate

## 開発環境

_昨日の実装が進み次第、随時追記_

### ・phpMyAdmin：http://localhost:8080/

### ・MailHog:http://localhost:8025/

# 使用技術（実行環境）

---

## ・PHP Version 8.1.34

## ・Laravel Framework 8.83.8

## ・mysql Ver 8.0.26

## ・nginx/1.21.1

## ・mailhog

# ER図

---

<img width="1783" height="3368" alt="勤怠アプリ　ER図" src="https://github.com/user-attachments/assets/41056a80-456d-4771-9e8a-9e4b345bd2a7" />

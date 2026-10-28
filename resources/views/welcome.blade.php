<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>KidTask – Quản Lý Việc Nhà & Rèn Thói Quen Tốt Cho Trẻ</title>

        <meta
        name="description"
        content="KidTask giúp cha mẹ giao nhiệm vụ hằng ngày, theo dõi tiến độ và rèn luyện thói quen tốt cho trẻ."
        />

        <meta name="robots" content="index, follow" />

        <link rel="canonical" href="https://KidTask.vn/" />

        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="KidTask" />
        <meta property="og:title" content="KidTask – Cùng Con Xây Dựng Thói Quen Tốt Mỗi Ngày" />
        <meta
        property="og:description"
        content="Giao nhiệm vụ, theo dõi tiến độ và cùng con xây dựng thói quen tốt mỗi ngày."
        />
        <meta property="og:url" content="https://KidTask.vn/" />
        <meta property="og:image" content="https://KidTask.vn/images/kidtask-og.png" />

        <meta name="twitter:card" content="summary_large_image" />
        <link rel="icon" type="image/svg+xml" href="/images/kidtask-favicon.png" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>

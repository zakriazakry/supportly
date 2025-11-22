<!DOCTYPE html>
<html>

<head>
    <title>اختر صفحة فيسبوك</title>
</head>

<body>
    <h1>اختر الصفحة لتفعيل البوت</h1>

    @if(session('error'))
    <p style="color: red">{{ session('error') }}</p>
    @endif

    <form method="POST" action="{{ route('facebook.select.page') }}">
        @csrf

        <label for="page_id">الصفحات المتاحة:</label>
        <select name="page_id" id="page_id" required>
            @foreach ($pages as $page)
            <option value="{{ $page['id'] }}">{{ $page['name'] }}</option>
            @endforeach
        </select>

        <button type="submit">تفعيل البوت على هذه الصفحة</button>
    </form>
</body>

</html>
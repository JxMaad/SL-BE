<!-- resources/views/laporan_absensi_mapel.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengembalian Buku Perbulan</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Laporan Pengembalian Buku Perbulan</h1>
    <table>
        <thead>
            <tr>
                <th>Returndate</th>
                <th>Buku</th>
                <th>User</th>
                <th>Peminjaman</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dataList as $data)
                <tr>
                    <td>{{ $data['returndate'] }}</td>
                    <td>{{ $data['book_id'] }}</td>
                    <td>{{ $data['user_id'] }}</td>
                    <td>{{ $data['borrow_id'] }}</td>
                    <td>{{ $data['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
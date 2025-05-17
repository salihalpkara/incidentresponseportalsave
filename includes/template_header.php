<?php
require_once("includes/session_start.php");
$pageTitle = $pageTitle ?? 'Incident Response Portal';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.0/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.datatables.net/2.3.0/js/dataTables.min.js"></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>


    <style>
        html,
        body {
            height: 100dvh;
            margin: 0;
        }

        .login-wrapper {
            min-height: 100dvh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
        }

        /* Example CSS for active button state */
        .btn-custom-active {
            background-color: #007bff;
            /* A distinct "active" color, e.g., Bootstrap primary blue */
            color: white !important;
            /* Ensure text is readable */
            border-color: #007bff;
        }

        .btn-custom-active:hover {
            background-color: #0069d9 !important;
            /* Slightly darker blue for hover, still clearly active */
            color: white !important;
            /* Keep text white */
            border-color: #0062cc !important;
            /* Adjust border to match darker blue */
        }

        @media (max-height: 600px) {
            .login-wrapper {
                align-items: flex-start;
                padding-top: 2rem;
            }
        }
    </style>

    <style>
        /* General page styles (if any) can go here */

        @media print {
            body {
                /* Tarayıcı uyumluluğu için üç özellik de eklendi */
                -webkit-print-color-adjust: exact !important; /* Eski Webkit */
                print-color-adjust: exact !important;         /* Standart (bazı eski araçlar için) */
            }

            /* Yazdırılmayacak elemanları gizle */
            #chartModeButtons,
            #dateGroupSelect,
            button[onclick="printReport()"], /* Yazdır butonu */
            nav, /* Navbar */
            .navbar,
            #template_navbar,
            #template_footer,
            footer { /* Diğer gizlenecekler */
                display: none !important;
            }

            /* Sayfa düzenini ayarla */
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                width: 100% !important;
                margin: 0 !important;
                padding: 15px !important; /* Kenar boşlukları için */
                box-shadow: none !important;
                border: none !important;
            }

            /* Grafikler (Resim olarak) */
            .chart-print-img {
                display: block !important;
                max-width: 100% !important;
                width: 100% !important;
                height: auto !important;
                page-break-inside: avoid !important; /* Sayfa bölünmesini engellemeye çalış */
                margin-bottom: 20px !important;
                border: 1px solid #ccc !important; /* Görünürlük için hafif kenarlık */
            }
            #miniPieChart-img { /* Mini grafik için özel boyut ve ortalama */
                max-width: 250px !important;
                margin-left: auto !important;
                margin-right: auto !important;
            }

            /* Tablolar */
            #incidentTableContainer,
            #assetTableContainer {
                page-break-inside: avoid !important; /* Tablo konteynerinin bölünmesini engelle */
                margin-top: 15px !important;
            }

            #incidentTableContainer table,
            #assetTableContainer table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 9pt !important;
                page-break-inside: auto; /* Tablonun kendisi bölünebilir */
            }
            #incidentTableContainer tr, /* Tablo satırlarının bölünmesini engelle */
            #assetTableContainer tr {
                page-break-inside: avoid !important;
            }

            /* Tablo Hücreleri ve Başlıkları - Kenarlık rengi #000 (siyah) yapıldı */
            #incidentTableContainer th, #incidentTableContainer td,
            #assetTableContainer th, #assetTableContainer td {
                border: 1px solid #000 !important; /* Daha belirgin kenarlık (SİYAH) */
                padding: 5px !important;
                color: black !important; /* Siyah metin */
                background-color: white !important; /* BEYAZ ARKA PLAN (Zebra için önemli) */
            }
            #incidentTableContainer th, #assetTableContainer th {
                background-color: #eee !important; /* Başlıklar için açık gri */
                font-weight: bold;
            }
            /* Bootstrap karanlık tema ve zebra etkisini print için sıfırla */
            .table-dark, .table-striped > tbody > tr:nth-of-type(odd) > * {
                background-color: white !important;
                color: black !important;
                --bs-table-bg: white;
                --bs-table-striped-bg: white;
                --bs-table-color: black;
                border-color: #000 !important; /* Kenarlık rengini burada da siyah yapalım */
            }

            /* Başlık */
            h2 {
                color: black !important;
                font-size: 16pt !important;
                text-align: center;
                margin-bottom: 25px;
                page-break-after: avoid;
            }

            /* Canvasları gizle (yerine resimler gelecek) */
            canvas {
                display: none !important;
            }
            img {
            max-width: 100% !important;
            height: auto !important;
            }
        }
    </style>

</head>

<body>
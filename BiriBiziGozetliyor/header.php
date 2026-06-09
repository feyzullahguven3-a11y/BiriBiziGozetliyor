<?php

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>Gözetim Merkezi ERP & CRM</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">

    <style>
        :root { 
            --bg-color: #f3f4f6; 
            --card-bg: #ffffff; 
            --text-main: #1f2937; 
            --text-muted: #6b7280; 
            --primary-color: #4f46e5; 
            --border-color: #e5e7eb; 
            --danger-color: #ef4444;
            --success-color: #10b981;
        }
        @media (prefers-color-scheme: dark) { 
            :root { 
                --bg-color: #111827; 
                --card-bg: #1f2937; 
                --text-main: #f9fafb; 
                --text-muted: #9ca3af; 
                --border-color: #374151; 
            } 
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); display: flex; min-height: 100vh; }
        
        .main-content { flex: 1; padding: 30px; overflow-y: auto; width: 100%;}
        
        .card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn { background: var(--primary-color); color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;}
        .btn-danger { background: var(--danger-color); }
        .btn-success { background: var(--success-color); }
        .action-btn { background: var(--primary-color); color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer;}
        
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px; }
        .form-group { flex: 1; min-width: 250px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: var(--text-muted); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-color); color: var(--text-main); outline: none;}
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 15px; }
        th { background: rgba(0,0,0,0.02); padding: 15px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid var(--border-color); }
        td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;}
        .bg-bekliyor { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .bg-kabul { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .bg-red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }


        
        .mobil-ust-bar { display: none; } /* Masaüstünde gizli */
        .karanlik-arkaplan { display: none; }

        @media (max-width: 768px) {
            body { flex-direction: column; }

            .sidebar {
                position: fixed !important;
                left: -300px; /* Ekranın dışında sakla */
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease-in-out;
                box-shadow: 5px 0 15px rgba(0,0,0,0.2);
            }
            .sidebar.acik { left: 0; } /* 3 Çizgiye basılınca ekrana gir */

            .mobil-ust-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 20px;
                background: var(--card-bg);
                border-bottom: 1px solid var(--border-color);
                position: sticky;
                top: 0;
                z-index: 998;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }

            .uc-cizgi-btn {
                background: none;
                border: none;
                font-size: 24px;
                color: var(--text-main);
                cursor: pointer;
                padding: 5px;
            }

            .karanlik-arkaplan.acik {
                display: block;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                backdrop-filter: blur(2px);
            }

            .main-content { padding: 15px; }
            .form-row { flex-direction: column; gap: 10px; }
            .form-group { width: 100%; }
            .data-table { white-space: nowrap; }
            .card { width: 100%; padding: 20px; }
        }
    </style>
</head>
<body>

    <div class="mobil-ust-bar">
        <div style="font-weight: 700; color: var(--primary-color); font-size: 18px;">
            <i class="fa-solid fa-eye"></i> Gözetim Merkezi
        </div>
        <button class="uc-cizgi-btn" id="ucCizgiBtn">
            <i class="fa-solid fa-bars"></i> </button>
    </div>

    <div class="karanlik-arkaplan" id="karanlikArkaplan"></div>

    <?php include 'sidebar.php'; ?>
    
    <main class="main-content">

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const btn = document.getElementById('ucCizgiBtn');
            const sidebar = document.getElementById('sidebar'); 
            const arkaplan = document.getElementById('karanlikArkaplan');

            if (btn && sidebar && arkaplan) {
                btn.addEventListener('click', function() {
                    sidebar.classList.add('acik');
                    arkaplan.classList.add('acik');
                });

                // Siyah boşluğa basıldığında menüyü kapat:
                arkaplan.addEventListener('click', function() {
                    sidebar.classList.remove('acik');
                    arkaplan.classList.remove('acik');
                });
            }
        });
    </script>
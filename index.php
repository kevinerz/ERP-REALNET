<?php
// index.php - PREMIUM LUXURY VERSION (ORIGINAL COLORS)
require_once 'webconfig.php';

// ==========================================================
// LOGIKA 1: DATABASE UMUM (Data Paket)
// ==========================================================
if (!isset($packages) || !is_array($packages)) {
    if (function_exists('connect_umum') && function_exists('get_internet_packages')) {
        $conn_umum = connect_umum();
        $packages = get_internet_packages($conn_umum);
        if ($conn_umum) $conn_umum->close();
    } else {
        $packages = [];
    }
}

// ==========================================================
// LOGIKA 2: DATABASE BACKBONE (Data Peta Google Maps)
// ==========================================================
$map_points = [];

if (function_exists('connect_backbone')) {
    $conn_backbone = connect_backbone();
    if ($conn_backbone) {
        $sql_map = "SELECT id, type, name, description, pop_area, lat, lng, status 
                    FROM points 
                    WHERE status = 'ACTIVE'";
        
        $stmt_map = $conn_backbone->prepare($sql_map);
        if ($stmt_map) {
            $stmt_map->execute();
            $res_map = $stmt_map->get_result();
            while ($row = $res_map->fetch_assoc()) {
                $row['lat'] = (float)$row['lat'];
                $row['lng'] = (float)$row['lng'];
                $map_points[] = $row;
            }
            $stmt_map->close();
        }
        $conn_backbone->close();
    }
}

$json_points = json_encode($map_points);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    
    <title>FUN Connect by Realnet - Internet Super Cepat</title>
    <link rel="shortcut icon" href="img/favicon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #FF5733;
            --primary-dark: #E63E24;
            --secondary-color: #FFC300;
            --tertiary-color: #33FF57;
            --light-bg: #FFFFFF;
            --soft-bg: #F0F9FF;
            --card-bg: #FFFFFF;
            --text-dark: #1F2937;
            --text-muted: #6B7280;
            --gradient: linear-gradient(135deg, #FF5733 0%, #FFC300 50%, #FF5733 100%);
            --gradient-blue: linear-gradient(135deg, #4CC9F0 0%, #9D4EDD 100%);
            --navbar-height: 90px;
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent; 
        }
        
        html { 
            scroll-behavior: smooth; 
            scroll-padding-top: calc(var(--navbar-height) + 20px); 
        }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--light-bg); 
            color: var(--text-dark); 
            overflow-x: hidden; 
            line-height: 1.6;
        }

        /* =====================================================
           TYPOGRAPHY
           ===================================================== */
        h1, .hero-title { 
            font-size: clamp(2.5rem, 5vw, 4.5rem); 
            font-weight: 900; 
            line-height: 1.1;
            letter-spacing: -1px;
        }
        
        h2, .section-title { 
            font-size: clamp(2rem, 4vw, 3.5rem); 
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        
        h3 { 
            font-size: clamp(1.5rem, 3vw, 2.5rem); 
            font-weight: 700;
        }
        
        p, .lead { 
            font-size: clamp(0.95rem, 1.2vw, 1.15rem); 
            font-weight: 400;
        }

        /* =====================================================
           GRADIENT TEXT
           ===================================================== */
        .text-gradient { 
            background: var(--gradient); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text;
        }

        .text-glow {
            text-shadow: 0 0 15px rgba(255, 87, 51, 0.4);
        }

        /* =====================================================
           BUTTONS - ENHANCED
           ===================================================== */
        .btn { 
            min-height: 48px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 700; 
            border-radius: 50px; 
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); 
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }

        .btn-fun { 
            background: var(--gradient); 
            border: none; 
            color: white; 
            padding: 12px 35px; 
            box-shadow: 0 10px 30px rgba(255, 87, 51, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-fun::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transition: left 0.5s ease;
        }

        .btn-fun:hover::before {
            left: 100%;
        }

        .btn-fun:hover, .btn-fun:active { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 40px rgba(255, 87, 51, 0.5); 
            color: white; 
        }
        
        .btn-light-fun { 
            background: white; 
            color: var(--primary-color); 
            border: none; 
            padding: 12px 35px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .btn-light-fun:hover { 
            background: #f8f9fa; 
            transform: translateY(-3px); 
            color: var(--primary-dark);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .btn-outline-fun { 
            background: transparent; 
            color: var(--primary-color); 
            border: 2px solid var(--primary-color); 
            padding: 12px 33px;
            transition: all 0.3s ease;
        }

        .btn-outline-fun:hover { 
            background: var(--gradient); 
            color: white; 
            border-color: transparent; 
            box-shadow: 0 10px 30px rgba(255, 87, 51, 0.3);
            transform: translateY(-3px);
        }

        /* =====================================================
           NAVBAR - PREMIUM
           ===================================================== */
        .navbar { 
            background-color: transparent; 
            transition: all 0.4s ease; 
            padding: 15px 0; 
            min-height: var(--navbar-height);
            position: fixed;
            width: 100%;
            z-index: 999;
            top: 0;
        }

        .navbar-scrolled { 
            background-color: rgba(255, 255, 255, 0.98) !important; 
            backdrop-filter: blur(15px);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.08);
            padding: 10px 0;
        }

        .nav-link { 
            color: var(--text-muted) !important; 
            font-weight: 600; 
            padding: 10px 15px !important;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 15px;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: calc(100% - 30px);
        }

        .nav-link:hover, .nav-link.active { 
            color: var(--primary-color) !important; 
        }

        .navbar-brand { 
            font-weight: 900; 
            font-size: 1.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-toggler { 
            border: none; 
            padding: 0; 
        }

        .navbar-toggler:focus { 
            box-shadow: none; 
        }

        /* =====================================================
           HERO SECTION
           ===================================================== */
        #home { 
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            padding-top: var(--navbar-height);
            position: relative;
            overflow: hidden;
        }

        #home::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 87, 51, 0.1), transparent);
            border-radius: 50%;
            filter: blur(60px);
        }

        #home::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 195, 0, 0.1), transparent);
            border-radius: 50%;
            filter: blur(60px);
        }

        .hero-img { 
            max-width: 100%; 
            height: auto; 
            animation: float-image 4s ease-in-out infinite;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.1));
            position: relative;
            z-index: 2;
        }

        @keyframes float-image {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =====================================================
           SECTIONS
           ===================================================== */
        .section-padding { 
            padding: clamp(60px, 8vw, 100px) 0; 
        }

        .section-title { 
            margin-bottom: 1rem; 
            color: var(--text-dark); 
            position: relative; 
            display: inline-block; 
        }

        .section-title::after { 
            content: ''; 
            position: absolute; 
            bottom: -10px; 
            left: 50%; 
            transform: translateX(-50%); 
            width: 80px; 
            height: 5px; 
            background: var(--gradient); 
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(255, 87, 51, 0.3);
        }

        .section-subtitle { 
            color: var(--text-muted); 
            margin-bottom: 3rem; 
            font-weight: 400;
            margin-top: 1.5rem;
        }

        /* =====================================================
           FEATURE CARDS
           ===================================================== */
        .feature-card {
            background: linear-gradient(135deg, rgba(255, 87, 51, 0.05) 0%, rgba(255, 195, 0, 0.05) 100%);
            border: 2px solid rgba(255, 87, 51, 0.1);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 87, 51, 0.1) 0%, rgba(255, 195, 0, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            border-color: var(--primary-color);
            box-shadow: 0 20px 50px rgba(255, 87, 51, 0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        /* =====================================================
           PRICING CARDS
           ===================================================== */
        #pricing { 
            background: linear-gradient(180deg, #F0F9FF 0%, #E0F2FE 100%); 
        }

        .pricing-card { 
            background: var(--card-bg); 
            border-radius: 25px; 
            padding: 2.5rem 1.5rem; 
            text-align: center; 
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); 
            height: 100%; 
            display: flex; 
            flex-direction: column; 
            border: 2px solid transparent; 
            position: relative; 
            overflow: hidden;
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .pricing-card:hover::before {
            opacity: 1;
        }

        .pricing-card:hover { 
            transform: translateY(-10px); 
            border-color: var(--primary-color);
            box-shadow: 0 25px 60px rgba(255, 87, 51, 0.15);
        }

        .pricing-card.popular { 
            border: 2px solid var(--primary-color); 
            transform: scale(1.02); 
            z-index: 2;
            box-shadow: 0 20px 50px rgba(255, 87, 51, 0.2);
        }

        .pricing-card.popular:hover { 
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 30px 70px rgba(255, 87, 51, 0.25);
        }

        .popular-badge { 
            position: absolute; 
            top: 20px; 
            right: -30px; 
            background: var(--gradient); 
            color: #fff; 
            padding: 5px 35px; 
            transform: rotate(45deg); 
            font-weight: 800; 
            font-size: 0.7rem;
            box-shadow: 0 8px 20px rgba(255, 87, 51, 0.3);
        }

        .package-speed { 
            font-size: clamp(3.5rem, 5vw, 5rem); 
            font-weight: 900; 
            background: var(--gradient); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
            margin: 15px 0 0; 
            line-height: 1; 
        }

        .package-price {
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 900;
            color: var(--primary-color);
            margin: 1.5rem 0 !important;
        }

        .package-features {
            list-style: none;
            text-align: left;
            margin: 1.5rem 0;
            flex-grow: 1;
        }

        .package-features li {
            padding: 0.8rem 0;
            color: var(--text-muted);
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .package-features li::before {
            content: '✓';
            color: var(--tertiary-color);
            font-weight: 900;
            font-size: 1.2rem;
        }

        /* =====================================================
           RESELLER & AFFILIATE SECTION
           ===================================================== */
        #reseller { 
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); 
        }

        #affiliate { 
            background: #fff; 
            position: relative; 
            overflow: hidden; 
        }

        .reseller-card {
            background: white;
            border-radius: 25px;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 15px 50px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
            height: 100%;
        }

        .reseller-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1), transparent);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .reseller-card:hover::before {
            top: -30%;
            right: -30%;
        }

        .reseller-card:hover {
            transform: translateY(-15px);
            border-color: #667eea;
            box-shadow: 0 25px 70px rgba(102, 126, 234, 0.2);
        }

        .reseller-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #667eea;
            filter: drop-shadow(0 5px 15px rgba(102, 126, 234, 0.2));
        }

        .reseller-title {
            color: #1F2937;
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .reseller-desc {
            color: #6B7280;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .reseller-benefits {
            text-align: left;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 15px;
        }

        .reseller-benefits li {
            padding: 0.7rem 0;
            color: #6B7280;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            list-style: none;
        }

        .reseller-benefits li::before {
            content: '✓';
            color: #33FF57;
            font-weight: 900;
            font-size: 1.1rem;
        }

        .affiliate-card {
            background: #fff; 
            border-radius: 30px; 
            padding: clamp(40px, 5vw, 60px); 
            box-shadow: 0 20px 60px rgba(0,0,0,0.08); 
            position: relative; 
            z-index: 1; 
            text-align: center; 
            border: 2px solid;
            border-image: linear-gradient(135deg, #FF5733, #FFC300) 1;
            transition: all 0.3s ease;
        }

        .affiliate-card:hover {
            box-shadow: 0 30px 80px rgba(0,0,0,0.12);
            transform: translateY(-10px);
        }

        .affiliate-icon-box { 
            width: clamp(80px, 10vw, 120px); 
            height: clamp(80px, 10vw, 120px); 
            background: var(--gradient); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 25px; 
            box-shadow: 0 15px 30px rgba(255, 87, 51, 0.3);
        }

        .affiliate-icon { 
            color: white; 
            font-size: clamp(2.5rem, 4vw, 4rem); 
        }

        .affiliate-benefits {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0,0,0,0.08);
        }

        .benefit-item {
            text-align: center;
            transition: all 0.3s ease;
        }

        .benefit-item:hover {
            transform: translateY(-5px);
        }

        .benefit-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        /* =====================================================
           COVERAGE & MAPS
           ===================================================== */
        #coverageMap { 
            width: 100%; 
            height: 550px; 
            border-radius: 0 0 25px 25px;
        }

        .map-container {
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            background: white;
        }

        .map-search-container { 
            padding: 15px; 
            background: white; 
            border-bottom: 1px solid rgba(0,0,0,0.08);
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .map-search-container input {
            border-radius: 50px !important;
            padding: 12px 20px !important;
            transition: all 0.3s ease;
            border: 1px solid #ddd !important;
        }

        .map-search-container input:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 15px rgba(255, 87, 51, 0.2);
        }

        .status-alert { 
            display: none; 
            padding: 15px; 
            border-radius: 12px; 
            margin-bottom: 20px; 
            font-weight: 600; 
            text-align: center; 
            font-size: 0.95rem;
            border: 2px solid;
        }

        .status-success { 
            background-color: #d1fae5; 
            color: #065f46; 
            border-color: #34d399;
        }

        .status-danger { 
            background-color: #fee2e2; 
            color: #991b1b; 
            border-color: #f87171;
        }

        /* =====================================================
           FOOTER
           ===================================================== */
        footer { 
            background: #1F2937; 
            color: white; 
            padding: 40px 0; 
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 87, 51, 0.1);
        }

        .footer-brand {
            font-size: 1.8rem;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .social-link {
            display: inline-flex;
            width: 45px;
            height: 45px;
            align-items: center;
            justify-content: center;
            color: white;
            border-radius: 50%;
            background: rgba(255, 87, 51, 0.1);
            transition: all 0.3s ease;
            margin: 0 0.5rem;
            border: 1px solid rgba(255, 87, 51, 0.2);
        }

        .social-link:hover {
            background: var(--gradient);
            transform: translateY(-5px);
            border-color: transparent;
        }

        /* =====================================================
           RESPONSIVE
           ===================================================== */
        @media (max-width: 991px) {
            .hero-img { 
                margin-top: 40px; 
                max-width: 80%; 
            }

            .hero-title, .section-title { 
                text-align: center; 
            }

            #home p, .section-subtitle { 
                text-align: center; 
            }

            #home .d-flex-lg { 
                justify-content: center; 
                display: flex; 
                flex-wrap: wrap; 
                gap: 10px; 
            }

            .btn-lg { 
                width: 100%; 
                margin-right: 0 !important; 
                margin-bottom: 10px; 
            }

            .navbar-collapse {
                background: white;
                padding: 20px;
                border-radius: 15px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                margin-top: 10px;
            }

            .reseller-card {
                margin-bottom: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            #coverageMap { 
                height: 50vh; 
                min-height: 350px; 
            }

            .map-search-container { 
                flex-direction: column; 
            }

            .map-search-container .btn { 
                width: 100%; 
                margin-top: 10px; 
            }
            
            .section-padding { 
                padding: 50px 0; 
            }

            .pricing-card.popular {
                transform: scale(1.01);
            }

            .pricing-card.popular:hover {
                transform: translateY(-15px) scale(1.03);
            }

            .affiliate-benefits {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 1.5rem;
            }

            .reseller-benefits {
                text-align: center;
            }

            .reseller-benefits li::before {
                display: none;
            }
        }

        /* =====================================================
           ANIMATIONS
           ===================================================== */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-alert {
            animation: slideDown 0.3s ease;
        }

        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
    </style>
</head>
<body data-bs-spy="scroll" data-bs-target="#navbar">
    
    <!-- NAVBAR -->
    <nav id="navbar" class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a href="#home" class="navbar-brand text-gradient">⚡ FUN CONNECT</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto text-center">
                    <li class="nav-item"><a class="nav-link" href="#home">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">Keunggulan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pricing">Paket</a></li>
                    <li class="nav-item"><a class="nav-link" href="#reseller">Reseller</a></li>
                    <li class="nav-item"><a class="nav-link" href="#affiliate">Mitra</a></li>
                    <li class="nav-item"><a class="nav-link" href="#coverage">Coverage</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Kontak</a></li>
                </ul>
                <div class="text-center mt-3 mt-lg-0">
                    <a href="https://datarealsolution.net/daftarku.php" class="btn btn-fun w-100 w-lg-auto">Daftar Sekarang!</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="home">
        <div class="container">
            <div class="row align-items-center gx-5">
                <div class="col-lg-7" data-aos="fade-right">
                    <h1 class="hero-title text-center text-lg-start">Internet <span class="text-gradient">Super Cepat</span> yang Bikin <span class="text-gradient">Ceria!</span></h1>
                    <p class="text-muted my-4 fs-5 text-center text-lg-start">Streaming tanpa buffering, gaming tanpa lag. Koneksi pas buat gaya hidup digitalmu.</p>
                    
                    <div class="d-flex-lg text-center text-lg-start">
                        <a href="#pricing" class="btn btn-fun btn-lg me-lg-3">🚀 LIHAT PAKET</a>
                        <a href="https://wa.me/6285545176427" class="btn btn-outline-fun btn-lg">💬 Tanya Dulu</a>
                    </div>
                </div>
                <div class="col-lg-5 text-center" data-aos="fade-left">
                    <img src="img/intro.png" alt="Internet Ceria" class="img-fluid hero-img">
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section id="about" class="section-padding">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="section-title">Kenapa Pilih Kami?</h2>
                <p class="section-subtitle mx-auto">Layanan internet fiber optik terbaik dengan jaminan kepuasan.</p>
            </div>
            <div class="row text-center g-4">
                <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="100">
                    <div class="feature-card">
                        <i class="fas fa-laugh-squint fa-3x mb-3 feature-icon"></i>
                        <h5 class="fw-bold">Bikin Happy</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="200">
                    <div class="feature-card">
                        <i class="fas fa-infinity fa-3x mb-3 feature-icon"></i>
                        <h5 class="fw-bold">Unlimited</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="300">
                    <div class="feature-card">
                        <i class="fas fa-tools fa-3x mb-3 feature-icon"></i>
                        <h5 class="fw-bold">Pasang Cepat</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="zoom-in" data-aos-delay="400">
                    <div class="feature-card">
                        <i class="fas fa-life-ring fa-3x mb-3 feature-icon"></i>
                        <h5 class="fw-bold">24/7 Support</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING SECTION -->
    <section id="pricing" class="section-padding">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="section-title">Pilih Paket Super Seru!</h2>
                <p class="section-subtitle">Harga terbaik untuk kecepatan maksimal.</p>
            </div>
            <div class="row g-4 justify-content-center">
                <?php if (empty($packages)): ?>
                    <div class="col-12 text-center" data-aos="fade-up">
                        <div class="alert alert-warning">Paket internet belum tersedia saat ini.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($packages as $index => $pkg): 
                        $is_popular = ($index == 1);
                        $delay = $index * 100;
                    ?>
                        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $delay ?>">
                            <div class="pricing-card <?= $is_popular ? 'popular' : '' ?>">
                                <?php if ($is_popular): ?><div class="popular-badge">BEST SELLER</div><?php endif; ?>
                                <h3 class="package-name"><?= htmlspecialchars($pkg['nama_paket']) ?></h3>
                                <p class="text-muted fw-bold small">Fiber Optik</p>
                                <p class="package-speed"><?= htmlspecialchars($pkg['kecepatan']) ?></p>
                                <p class="text-muted fw-bold">Mbps Simetris ⚡</p>
                                <p class="package-price"><?= htmlspecialchars(format_rupiah($pkg['harga'])) ?> <span class="fs-6 fw-normal text-muted">/bln</span></p>
                                
                                <ul class="package-features">
                                    <?php 
                                    $features = explode(',', htmlspecialchars($pkg['deskripsi']));
                                    foreach(array_slice($features, 0, 5) as $feature) {
                                        if (trim($feature)) echo '<li>'.trim($feature).'</li>';
                                    }
                                    ?>
                                </ul>
                                <div class="mt-auto">
                                    <a href="https://datarealsolution.net/daftarku.php?paket=<?= urlencode($pkg['nama_paket']) ?>" class="btn btn-fun w-100">PILIH PAKET</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- RESELLER SECTION - NEW -->
    <section id="reseller" class="section-padding">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="section-title">Program Reseller</h2>
                <p class="section-subtitle mx-auto">Daftarkan layanan internet Anda dan kelola bisnis dengan platform kami.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Reseller Card 1 -->
                <div class="col-lg-5 col-md-6" data-aos="fade-right" data-aos-delay="100">
                    <div class="reseller-card">
                        <i class="fas fa-network-wired reseller-icon"></i>
                        <h3 class="reseller-title">Manage Jaringan Anda</h3>
                        <p class="reseller-desc">Dashboard lengkap untuk mengelola customer, billing, dan layanan internet Anda dengan mudah dan profesional.</p>
                        <ul class="reseller-benefits">
                            <li>Billing & Invoice Otomatis</li>
                            <li>Customer Management System</li>
                            <li>Monitoring Network Real-time</li>
                            <li>Support Teknis 24/7</li>
                            <li>API Integration</li>
                        </ul>
                        <a href="https://mitra.datarealsolution.net" target="_blank" class="btn btn-fun w-100">🚀 Dashboard Reseller</a>
                    </div>
                </div>

                <!-- Reseller Card 2 -->
                <div class="col-lg-5 col-md-6" data-aos="fade-left" data-aos-delay="200">
                    <div class="reseller-card">
                        <i class="fas fa-chart-line reseller-icon"></i>
                        <h3 class="reseller-title">Tools & Analytics</h3>
                        <p class="reseller-desc">Laporan lengkap dan analytics mendalam untuk membantu bisnis Anda berkembang dengan strategi yang tepat.</p>
                        <ul class="reseller-benefits">
                            <li>Revenue Analytics</li>
                            <li>Customer Insights</li>
                            <li>Marketing Tools</li>
                            <li>Automated Reports</li>
                            <li>Growth Metrics</li>
                        </ul>
                        <a href="https://mitra.datarealsolution.net/" target="_blank" class="btn btn-outline-fun w-100">📊 Login Reseller</a>
                    </div>
                </div>
            </div>

            <!-- Reseller Benefits Section -->
            <div class="row mt-5" data-aos="fade-up">
                <div class="col-12">
                    <div class="alert alert-light border-2 border-primary p-4 rounded-4" style="border-color: #667eea !important;">
                        <h5 class="fw-bold mb-3"><i class="fas fa-star text-warning me-2"></i>Keuntungan Menjadi Reseller</h5>
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: #667eea;">📈</div>
                                    <h6 class="fw-bold small mt-2">Revenue Sharing</h6>
                                    <p class="small text-muted">Dapatkan komisi menarik setiap transaksi</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: #667eea;">⚙️</div>
                                    <h6 class="fw-bold small mt-2">White Label Ready</h6>
                                    <p class="small text-muted">Branding sendiri untuk bisnis Anda</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: #667eea;">🎓</div>
                                    <h6 class="fw-bold small mt-2">Training & Support</h6>
                                    <p class="small text-muted">Pelatihan lengkap dan support teknis</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-center">
                                    <div style="font-size: 2rem; color: #667eea;">🌐</div>
                                    <h6 class="fw-bold small mt-2">Scalable Solution</h6>
                                    <p class="small text-muted">Infrastruktur yang tumbuh dengan bisnis</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Button -->
            <div class="row mt-4" data-aos="fade-up">
                <div class="col-12 text-center">
                    <a href="https://wa.me/6285545176427?text=Saya%20tertarik%20dengan%20program%20reseller%20FUN%20CONNECT" target="_blank" class="btn btn-fun btn-lg">
                        <i class="fab fa-whatsapp me-2"></i>Hubungi Kami untuk Reseller
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- AFFILIATE SECTION -->
    <section id="affiliate" class="section-padding">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-12">
                    <div class="affiliate-card" data-aos="zoom-in">
                        <div class="affiliate-icon-box">
                             <i class="fas fa-hand-holding-usd affiliate-icon"></i>
                        </div>

                        <h2 class="section-title text-gradient mb-2">Mau Cuan Tambahan?</h2>
                        <h3 class="h4 text-muted mb-4">Gabung Program Afiliasi RealNet!</h3>
                        
                        <p class="mx-auto text-muted mb-5" style="max-width: 700px;">
                            Dapatkan penghasilan pasif dengan mudah. Cukup referensikan teman atau tetangga, komisi cair tiap bulan!
                        </p>
                        
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                            <a href="https://market.datarealsolution.net" target="_blank" class="btn btn-fun btn-lg">
                                <i class="fas fa-rocket me-2"></i> DAFTAR SEKARANG
                            </a>
                            <a href="https://wa.me/6285545176427?text=Info%20Afiliasi" target="_blank" class="btn btn-outline-fun btn-lg">
                                <i class="fab fa-whatsapp me-2"></i> TANYA INFO
                            </a>
                        </div>

                        <div class="affiliate-benefits">
                            <div class="benefit-item">
                                <i class="fas fa-percentage benefit-icon"></i>
                                <h6 class="fw-bold small">Komisi Tinggi</h6>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-chart-line benefit-icon"></i>
                                <h6 class="fw-bold small">Transparan</h6>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-wallet benefit-icon"></i>
                                <h6 class="fw-bold small">Cepat Cair</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- COVERAGE SECTION -->
    <section id="coverage" class="section-padding" style="background: linear-gradient(180deg, #F0F9FF 0%, #FFFFFF 100%);">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="section-title">Cek Jangkauan</h2>
                <p class="section-subtitle mx-auto" style="max-width: 700px;">Cek lokasi Anda di peta. Gunakan kolom pencarian atau klik tombol <strong>"Lokasi Saya"</strong>.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-8" data-aos="fade-right">
                    <div class="map-container">
                        <div class="map-search-container d-flex gap-2 align-items-center">
                            <div class="input-group input-group-lg w-100">
                                <span class="input-group-text bg-white border-end-0 ps-3"><i class="fas fa-search text-primary"></i></span>
                                <input id="pac-input" type="text" class="form-control border-start-0" placeholder="Cari desa / jalan..." style="font-size: 1rem;">
                            </div>
                            <button class="btn btn-fun px-4" onclick="locateUser()" title="Gunakan Lokasi Saya">
                                <i class="fas fa-crosshairs"></i>
                            </button>
                        </div>
                        <div id="coverageMap"></div>
                    </div>
                </div>

                <div class="col-lg-4" data-aos="fade-left">
                    <div class="card border-0 shadow-lg mb-4" style="border-radius: 20px; background: var(--gradient); color: white; padding: 25px;">
                        <h5 class="fw-bold mb-3 text-center"><i class="fas fa-signal"></i> Network Stats</h5>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-center p-2 rounded-3" style="background: rgba(255,255,255,0.2);">
                                    <div class="h3 fw-bold mb-0" id="totalPoints">0</div>
                                    <small style="font-size: 0.75rem;">Active ODP</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 rounded-3" style="background: rgba(255,255,255,0.2);">
                                    <div class="h3 fw-bold mb-0" id="totalArea">0</div>
                                    <small style="font-size: 0.75rem;">Area (km²)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-lg p-4 mb-4" style="border-radius: 20px;">
                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary"></i> Legenda</h6>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2"><img src="http://maps.google.com/mapfiles/ms/icons/green-dot.png" width="18"> ODP Aktif</li>
                            <li class="mb-2"><span style="display:inline-block;width:12px;height:12px;background:rgba(79, 172, 254, 0.4);border:1px solid #4facfe;border-radius:50%;margin-right:5px;"></span> Radius 100m</li>
                            <li><img src="http://maps.google.com/mapfiles/ms/icons/red-dot.png" width="18"> Lokasi Anda</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="resultSection" class="row mt-4" data-aos="fade-up">
                <div class="col-12">
                    <div class="card border-0 shadow-lg p-4" style="border-radius: 20px; background: #fff;">
                         <h5 class="fw-bold mb-3 text-center">📍 Hasil Pengecekan Lokasi</h5>
                         
                         <div id="coverageStatus" class="status-alert">
                             <span id="statusIcon"></span> <span id="statusText"></span>
                         </div>

                         <div class="row g-3 align-items-center">
                             <div class="col-6 d-none d-md-block">
                                 <input type="text" id="userLat" class="form-control" placeholder="Latitude" readonly>
                             </div>
                             <div class="col-6 d-none d-md-block">
                                 <input type="text" id="userLng" class="form-control" placeholder="Longitude" readonly>
                             </div>
                             <div class="col-12">
                                 <button onclick="sendWa()" class="btn btn-fun w-100 py-3"><i class="fab fa-whatsapp fa-lg me-2"></i> KIRIM HASIL KE ADMIN</button>
                             </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section id="contact" class="section-padding">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up"><h2 class="section-title">Hubungi Kami</h2></div>
            <div class="row g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="map-responsive h-100 rounded-4 overflow-hidden shadow-lg" style="min-height: 300px;">
                       <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15842.132890250631!2d109.07727196025066!3d-6.940602059080614!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6fca783d5a498b%3A0xc54032d664c39130!2sDesa%20Sengon%2C%20Tanjung%2C%20Brebes%20Regency%2C%20Central%20Java!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid" width="100%" height="100%" style="border:0; min-height: 300px;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="p-4 rounded-4 h-100 shadow-lg bg-white">
                        <h4 class="mb-4 fw-bold">Kontak Info</h4>
                        <div class="mb-4 d-flex"><i class="fas fa-map-marker-alt fa-2x text-primary me-3"></i> <div><strong>Alamat:</strong><br>Jalan Kartini Gang Cempaka, Desa Sengon, Tanjung, Brebes</div></div>
                        <div class="mb-4 d-flex"><i class="fas fa-phone fa-2x text-primary me-3"></i> <div><strong>Telepon/WA:</strong><br>+62 855-4517-6427</div></div>
                        <div class="mb-4 d-flex"><i class="fas fa-envelope fa-2x text-primary me-3"></i> <div><strong>Email:</strong><br>admin@datarealsolution.net</div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="text-center">
        <div class="container">
            <h4 class="footer-brand">⚡ FUN CONNECT</h4>
            <div class="mb-4">
                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
            </div>
            <p class="text-white-50 mb-0">© <?= date('Y') ?> PT Real Data Solusindo.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDH4s_S0mOhLisPV_3e3SRXai11dZwA7dY&libraries=places,geometry&callback=initMap" async defer></script>

    <script>
        AOS.init({ duration: 800, once: true, offset: 50 });

        const navbar = document.getElementById('navbar');
        window.onscroll = () => {
            if (window.scrollY > 50) navbar.classList.add('navbar-scrolled');
            else navbar.classList.remove('navbar-scrolled');
        };

        // Close mobile menu on click
        document.querySelectorAll('.nav-link').forEach(l => {
            l.addEventListener('click', () => {
                const bsCollapse = new bootstrap.Collapse(document.getElementById('navbarNav'), {toggle:false});
                bsCollapse.hide();
            })
        });

        // ==========================================
        // GOOGLE MAPS LOGIC
        // ==========================================
        let map;
        let userMarker;
        let allPoints = []; 

        function initMap() {
            const pointsData = <?php echo $json_points; ?>;
            if(pointsData) document.getElementById('totalPoints').innerText = pointsData.length;

            const initialLat = pointsData.length > 0 ? parseFloat(pointsData[0].lat) : -6.9406;
            const initialLng = pointsData.length > 0 ? parseFloat(pointsData[0].lng) : 109.0773;

            map = new google.maps.Map(document.getElementById("coverageMap"), {
                center: { lat: initialLat, lng: initialLng },
                zoom: 13,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER },
                styles: [{ featureType: "poi", elementType: "labels", stylers: [{ visibility: "off" }] }]
            });

            if (pointsData && pointsData.length > 0) {
                pointsData.forEach(point => {
                    const pos = { lat: parseFloat(point.lat), lng: parseFloat(point.lng) };
                    allPoints.push(pos); 

                    const iconUrl = point.type === 'ODC' ? "http://maps.google.com/mapfiles/ms/icons/yellow-dot.png" : "http://maps.google.com/mapfiles/ms/icons/green-dot.png";
                    
                    const marker = new google.maps.Marker({ position: pos, map: map, title: point.name, icon: iconUrl });

                    new google.maps.Circle({
                        strokeColor: "#4facfe", strokeOpacity: 0.8, strokeWeight: 1,
                        fillColor: "#4facfe", fillOpacity: 0.15,
                        map: map, center: pos, radius: 100, clickable: false 
                    });

                    const infoContent = `<div style="color:black;padding:5px;text-align:center;"><strong>${point.name}</strong><br><span style="font-size:12px;background:#eee;padding:2px 5px;border-radius:4px;">${point.type}</span></div>`;
                    const infoWindow = new google.maps.InfoWindow({ content: infoContent });
                    marker.addListener("click", () => infoWindow.open(map, marker));
                });
                calculateArea(allPoints);
            }

            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.Autocomplete(input);
            searchBox.bindTo("bounds", map);

            searchBox.addListener("place_changed", () => {
                const place = searchBox.getPlace();
                if (!place.geometry || !place.geometry.location) { alert("Lokasi tidak ditemukan."); return; }
                if (place.geometry.viewport) map.fitBounds(place.geometry.viewport);
                else { map.setCenter(place.geometry.location); map.setZoom(17); }
                placeUserMarker(place.geometry.location);
            });

            map.addListener("click", (event) => { placeUserMarker(event.latLng); });
        }

        function locateUser() {
            if (navigator.geolocation) {
                const btn = document.querySelector('button[onclick="locateUser()"]');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = { lat: position.coords.latitude, lng: position.coords.longitude };
                        map.setCenter(pos);
                        map.setZoom(18);
                        placeUserMarker(pos);
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    },
                    (error) => {
                        alert("Gagal mendeteksi lokasi. Pastikan GPS aktif.");
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else { alert("Browser Anda tidak mendukung Geolocation."); }
        }

        function placeUserMarker(location) {
            if (!(location instanceof google.maps.LatLng)) location = new google.maps.LatLng(location.lat, location.lng);
            if (userMarker) userMarker.setMap(null);

            userMarker = new google.maps.Marker({
                position: location, map: map, draggable: true,
                animation: google.maps.Animation.DROP,
                icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
            });

            updateCoordinateInputs(location);
            checkCoverage(location); 

            document.getElementById('resultSection').scrollIntoView({ behavior: 'smooth', block: 'center' });

            userMarker.addListener("dragend", () => {
                const newPos = userMarker.getPosition();
                updateCoordinateInputs(newPos);
                checkCoverage(newPos);
            });
        }

        function checkCoverage(userLatLng) {
            let isCovered = false;
            let nearestDistance = Infinity;

            allPoints.forEach(point => {
                const odpLatLng = new google.maps.LatLng(point.lat, point.lng);
                const distance = google.maps.geometry.spherical.computeDistanceBetween(userLatLng, odpLatLng);
                if (distance < nearestDistance) nearestDistance = distance;
                if (distance <= 100) isCovered = true;
            });

            const statusBox = document.getElementById("coverageStatus");
            const statusIcon = document.getElementById("statusIcon");
            const statusText = document.getElementById("statusText");

            statusBox.style.display = "block";
            statusBox.className = "status-alert " + (isCovered ? "status-success" : "status-danger");
            const distanceStr = (nearestDistance === Infinity) ? "?" : Math.round(nearestDistance);

            if (isCovered) {
                statusIcon.innerHTML = "<i class='fas fa-check-circle fa-2x mb-2'></i>";
                statusText.innerHTML = `<br>SELAMAT! LOKASI TERCOVER<br><small style="font-weight:normal;">Jarak ODP: ${distanceStr}m</small>`;
            } else {
                statusIcon.innerHTML = "<i class='fas fa-times-circle fa-2x mb-2'></i>";
                statusText.innerHTML = `<br>MAAF, BELUM TERCOVER<br><small style="font-weight:normal;">Jarak ODP: ${distanceStr}m (Max 100m)</small>`;
            }
        }

        function updateCoordinateInputs(latLng) {
            document.getElementById("userLat").value = latLng.lat().toFixed(7);
            document.getElementById("userLng").value = latLng.lng().toFixed(7);
        }

        function sendWa() {
            const lat = document.getElementById("userLat").value;
            const lng = document.getElementById("userLng").value;
            const statusDiv = document.getElementById("statusText");
            let statusMsg = statusDiv.innerText || statusDiv.textContent;
            statusMsg = statusMsg.replace(/\n/g, " ").trim();

            if(!lat || !lng) { alert("Tentukan lokasi dulu!"); return; }
            
            const text = `Halo Admin,%0A%0ASaya ingin cek ketersediaan jaringan.%0A%0A*Status Web:* ${statusMsg}%0A*Lokasi:* ${lat}, ${lng}%0A%0ALink Maps: https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
            window.open(`https://wa.me/6285545176427?text=${text}`, '_blank');
        }

        function calculateArea(points) {
            if (points.length < 3) return;
            const pts = points.map(p => ({lat: p.lat, lng: p.lng}));
            pts.sort((a, b) => a.lat == b.lat ? a.lng - b.lng : a.lat - b.lat);
            const cross = (o, a, b) => (a.lng - o.lng) * (b.lat - o.lat) - (a.lat - o.lat) * (b.lng - o.lng);
            const lower = []; for (let p of pts) { while (lower.length >= 2 && cross(lower[lower.length - 2], lower[lower.length - 1], p) <= 0) lower.pop(); lower.push(p); }
            const upper = []; for (let i = pts.length - 1; i >= 0; i--) { while (upper.length >= 2 && cross(upper[upper.length - 2], upper[upper.length - 1], pts[i]) <= 0) upper.pop(); upper.push(pts[i]); }
            const hullPoints = lower.slice(0, lower.length - 1).concat(upper.slice(0, upper.length - 1));
            
            const polygonCoords = hullPoints.map(p => new google.maps.LatLng(p.lat, p.lng));
            const polygon = new google.maps.Polygon({ paths: polygonCoords, strokeColor: "#00f2fe", strokeOpacity: 0.8, strokeWeight: 2, fillColor: "#00f2fe", fillOpacity: 0.1, map: map });

            if(google.maps.geometry && google.maps.geometry.spherical) {
                 const areaM2 = google.maps.geometry.spherical.computeArea(polygon.getPath());
                 document.getElementById('totalArea').innerText = (areaM2 / 1000000).toFixed(2);
            }
        }
    </script>
</body>
</html>
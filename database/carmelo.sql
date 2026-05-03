-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 03-05-2026 a las 23:00:00
-- Versión del servidor: 5.7.24
-- Versión de PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `carmelo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `artworks`
--

CREATE TABLE `artworks` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` smallint(5) UNSIGNED DEFAULT NULL,
  `dimensions` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `technique` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `artworks`
--

INSERT INTO `artworks` (`id`, `slug`, `title`, `year`, `dimensions`, `technique`, `description`, `tags_json`, `image_path`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 'trio', 'Trio', NULL, 'Dimensiones no indicadas', 'Tecnica no indicada', 'Obra incorporada desde la carpeta de ejemplos iniciales.', '[\"seres carmelianos\"]', '/assets/artworks/trio.jpeg', 0, '2026-04-25 12:03:00', '2026-04-25 12:03:00'),
(2, 'pareja-espaldas', 'Pareja Espaldas', NULL, '20x90', 'Mixta', 'seres indescriptibles', '[\"amores imposibles\"]', '/assets/artworks/parejaEspaldas.jpeg', 0, '2026-04-25 12:02:00', '2026-04-25 12:02:00'),
(3, 'paseando-perro', 'Paseando Perro', NULL, 'Dimensiones no indicadas', 'Tecnica no indicada', 'Obra incorporada desde la carpeta de ejemplos iniciales.', '[\"amores imposibles\"]', '/assets/artworks/PaseandoPerro.jpeg', 1, '2026-04-25 12:01:00', '2026-04-25 12:01:00'),
(4, 'fotocasa', 'Fotocasa', NULL, 'Dimensiones no indicadas', 'Tecnica no indicada', 'Obra incorporada desde la carpeta de ejemplos iniciales.', '[\"retratos\"]', '/assets/artworks/fotocasa.jpeg', 0, '2026-04-25 12:00:00', '2026-04-25 12:00:00'),
(5, 'parque-infantil-surrealista', 'Parque Infantil Surrealista', NULL, '40x30', 'Mixta Digital', 'Perpespectiva carmeliana sin futuro definido', '[\"paisajes\"]', '/assets/artworks/1777116715500-parque-infantil-surrealista.jpeg', 0, '2026-04-25 11:31:55', '2026-04-25 11:31:55'),
(6, 'obra-2', '2', NULL, 'Dimensiones no indicadas', 'Tecnica no indicada', 'Obra detectada automaticamente en la carpeta de imagenes.', '[\"sin clasificar\"]', '/assets/artworks/2.jpeg', 0, '2026-04-25 11:15:20', '2026-04-25 11:15:20'),
(7, 'atardecer', 'atardecer', NULL, '20x30', 'digital', 'ZZZZ', '[\"sin clasificar\"]', '/assets/artworks/3.jpeg', 0, '2026-04-25 11:15:20', '2026-04-25 11:15:20'),
(8, 'obra-4', '4', NULL, 'Dimensiones no indicadas', 'Tecnica no indicada', 'Obra detectada automaticamente en la carpeta de imagenes.', '[\"sin clasificar\"]', '/assets/artworks/4.jpeg', 0, '2026-04-25 11:15:20', '2026-04-25 11:15:20'),
(9, 'lateral', 'Lateral', NULL, 'Dimensiones no indicadas', 'Tecnica no indicada', 'Obra detectada automaticamente en la carpeta de imagenes.', '[\"sin clasificar\"]', '/assets/artworks/lateral.jpeg', 0, '2026-04-25 11:15:20', '2026-04-25 11:15:20'),
(10, 'obra-1', '1', NULL, 'Dimensiones no indicadas', 'Tecnica no indicada', 'Obra detectada automaticamente en la carpeta de imagenes.', '[\"sin clasificar\"]', '/assets/artworks/1.jpeg', 0, '2026-04-25 11:15:20', '2026-04-25 11:15:20'),
(11, 'paisaje-galactico', 'Paisaje Galactico', 2026, '30x70', 'Mixta', 'Paisaje oscuro como mi mente', '[\"paisajes\"]', '/uploads/artworks/1777848450-paisaje-galactico.jpeg', 0, '2026-05-04 00:47:30', '2026-05-04 00:47:30');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `artworks`
--
ALTER TABLE `artworks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `artworks`
--
ALTER TABLE `artworks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100424
 Source Host           : localhost:3306
 Source Schema         : dbscan

 Target Server Type    : MySQL
 Target Server Version : 100424
 File Encoding         : 65001

 Date: 09/05/2025 00:58:00
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for clustering_results
-- ----------------------------
DROP TABLE IF EXISTS `clustering_results`;
CREATE TABLE `clustering_results`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cluster_count` int(11) NOT NULL,
  `epsilon` float NOT NULL,
  `min_samples` int(11) NULL DEFAULT NULL,
  `min_points` int(11) NOT NULL,
  `data_points` int(11) NOT NULL,
  `outliers` int(11) NOT NULL,
  `execution_time` float NOT NULL,
  `date_generated` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 117 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of clustering_results
-- ----------------------------
INSERT INTO `clustering_results` VALUES (114, 1, 0.5, NULL, 3, 27, 2, 1.0791, '2025-04-29 16:48:58');
INSERT INTO `clustering_results` VALUES (115, 1, 0.5, NULL, 3, 27, 2, 0.9321, '2025-04-29 16:49:05');
INSERT INTO `clustering_results` VALUES (116, 1, 0.5, NULL, 3, 28, 2, 1.0931, '2025-04-29 16:49:12');

-- ----------------------------
-- Table structure for diabetes_data
-- ----------------------------
DROP TABLE IF EXISTS `diabetes_data`;
CREATE TABLE `diabetes_data`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wilayah` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jumlah_penduduk` int(11) NOT NULL,
  `jumlah_penderita` int(11) NOT NULL,
  `jumlah_kematian` int(11) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `cluster` int(11) NULL DEFAULT NULL,
  `tahun` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 150 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of diabetes_data
-- ----------------------------
INSERT INTO `diabetes_data` VALUES (68, 'Baktiya', 38057, 28, 1, '2025-04-29 16:40:39', 0, 2021);
INSERT INTO `diabetes_data` VALUES (69, 'Baktiya', 38057, 28, 1, '2025-04-29 16:47:19', 0, 2021);
INSERT INTO `diabetes_data` VALUES (70, 'Baktiya Barat', 19117, 40, 2, '2025-04-29 16:47:19', -1, 2021);
INSERT INTO `diabetes_data` VALUES (71, 'Banda Baro', 7951, 20, 1, '2025-04-29 16:47:19', -1, 2021);
INSERT INTO `diabetes_data` VALUES (72, 'Cot Girek', 20428, 6, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (73, 'Dewantara', 45951, 381, 19, '2025-04-29 16:47:19', -1, 2021);
INSERT INTO `diabetes_data` VALUES (74, 'Geuredong Pase', 5631, 3, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (75, 'Kuta Makmur', 26742, 9, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (76, 'Langkahan', 22591, 3, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (77, 'Lapang', 8864, 6, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (78, 'Lhoksukon', 50134, 375, 15, '2025-04-29 16:47:19', -1, 2021);
INSERT INTO `diabetes_data` VALUES (79, 'Matang Kuli', 18927, 0, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (80, 'Meurah Mulia', 21306, 5, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (81, 'Muara Batu', 27996, 5, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (82, 'Nibong', 10921, 54, 3, '2025-04-29 16:47:19', -1, 2021);
INSERT INTO `diabetes_data` VALUES (83, 'Nisam', 19853, 51, 1, '2025-04-29 16:47:19', 0, 2021);
INSERT INTO `diabetes_data` VALUES (84, 'Nisam Antara', 13554, 9, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (85, 'Paya Bakong', 15804, 89, 0, '2025-04-29 16:47:19', 0, 2021);
INSERT INTO `diabetes_data` VALUES (86, 'Pirak Timu', 8912, 0, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (87, 'Samudera', 27326, 26, 0, '2025-04-29 16:47:19', 0, 2021);
INSERT INTO `diabetes_data` VALUES (88, 'Sawang', 39470, 5, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (89, 'Seunuddon', 26162, 5, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (90, 'Simpang Kramat', 10049, 0, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (91, 'Syamtalira Aron', 19576, 35, 0, '2025-04-29 16:47:19', 0, 2021);
INSERT INTO `diabetes_data` VALUES (92, 'Syamtalira Bayu', 22671, 0, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (93, 'Tanah Jambo Aye', 44578, 0, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (94, 'Tanah Luas', 25425, 0, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (95, 'Tanah Pasir', 10110, 0, 0, '2025-04-29 16:47:19', 1, 2021);
INSERT INTO `diabetes_data` VALUES (96, 'Baktiya', 38534, 43, 2, '2025-04-29 16:47:19', 0, 2022);
INSERT INTO `diabetes_data` VALUES (97, 'Baktiya Barat', 19292, 29, 1, '2025-04-29 16:47:19', 0, 2022);
INSERT INTO `diabetes_data` VALUES (98, 'Banda Baro', 7991, 37, 2, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (99, 'Cot Girek', 20591, 12, 1, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (100, 'Dewantara', 46107, 586, 29, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (101, 'Geuredong Pase', 5740, 11, 1, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (102, 'Kuta Makmur', 27161, 17, 1, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (103, 'Langkahan', 22708, 10, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (104, 'Lapang', 8940, 9, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (105, 'Lhoksukon', 50638, 502, 25, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (106, 'Matang Kuli', 19135, 6, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (107, 'Meurah Mulia', 21634, 8, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (108, 'Muara Batu', 28295, 115, 6, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (109, 'Nibong', 11086, 13, 1, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (110, 'Nisam', 20084, 270, 14, '2025-04-29 16:47:19', -1, 2022);
INSERT INTO `diabetes_data` VALUES (111, 'Nisam Antara', 13669, 26, 1, '2025-04-29 16:47:19', 0, 2022);
INSERT INTO `diabetes_data` VALUES (112, 'Paya Bakong', 16088, 9, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (113, 'Pirak Timu', 9044, 5, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (114, 'Samudera', 27559, 39, 0, '2025-04-29 16:47:19', 0, 2022);
INSERT INTO `diabetes_data` VALUES (115, 'Sawang', 39957, 45, 0, '2025-04-29 16:47:19', 0, 2022);
INSERT INTO `diabetes_data` VALUES (116, 'Seunuddon', 26392, 6, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (117, 'Simpang Kramat', 10160, 0, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (118, 'Syamtalira Aron', 19847, 13, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (119, 'Syamtalira Bayu', 22996, 0, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (120, 'Tanah Jambo Aye', 45022, 0, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (121, 'Tanah Luas', 25707, 0, 0, '2025-04-29 16:47:19', 1, 2022);
INSERT INTO `diabetes_data` VALUES (122, 'Tanah Pasir', 10263, 32, 0, '2025-04-29 16:47:19', 0, 2022);
INSERT INTO `diabetes_data` VALUES (123, 'Baktiya', 39016, 26, 1, '2025-04-29 16:47:19', 0, 2023);
INSERT INTO `diabetes_data` VALUES (124, 'Baktiya Barat', 19468, 32, 2, '2025-04-29 16:47:19', -1, 2023);
INSERT INTO `diabetes_data` VALUES (125, 'Banda Baro', 8031, 22, 1, '2025-04-29 16:47:19', 0, 2023);
INSERT INTO `diabetes_data` VALUES (126, 'Cot Girek', 20756, 8, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (127, 'Dewantara', 46264, 839, 42, '2025-04-29 16:47:19', -1, 2023);
INSERT INTO `diabetes_data` VALUES (128, 'Geuredong Pase', 5851, 8, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (129, 'Kuta Makmur', 27587, 24, 1, '2025-04-29 16:47:19', 0, 2023);
INSERT INTO `diabetes_data` VALUES (130, 'Langkahan', 22826, 10, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (131, 'Lapang', 9017, 15, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (132, 'Lhoksukon', 51149, 409, 20, '2025-04-29 16:47:19', -1, 2023);
INSERT INTO `diabetes_data` VALUES (133, 'Matang Kuli', 19346, 10, 1, '2025-04-29 16:47:19', -1, 2023);
INSERT INTO `diabetes_data` VALUES (134, 'Meurah Mulia', 21967, 12, 1, '2025-04-29 16:47:19', -1, 2023);
INSERT INTO `diabetes_data` VALUES (135, 'Muara Batu', 28598, 227, 11, '2025-04-29 16:47:19', 0, 2023);
INSERT INTO `diabetes_data` VALUES (136, 'Nibong', 11254, 12, 1, '2025-04-29 16:47:19', -1, 2023);
INSERT INTO `diabetes_data` VALUES (137, 'Nisam', 20317, 316, 16, '2025-04-29 16:47:19', -1, 2023);
INSERT INTO `diabetes_data` VALUES (138, 'Nisam Antara', 13785, 44, 2, '2025-04-29 16:47:19', 0, 2023);
INSERT INTO `diabetes_data` VALUES (139, 'Paya Bakong', 16377, 8, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (140, 'Pirak Timu', 9178, 14, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (141, 'Samudera', 27794, 40, 0, '2025-04-29 16:47:19', 0, 2023);
INSERT INTO `diabetes_data` VALUES (142, 'Sawang', 40449, 96, 0, '2025-04-29 16:47:19', 0, 2023);
INSERT INTO `diabetes_data` VALUES (143, 'Seunuddon', 26624, 9, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (144, 'Simpang Kramat', 10272, 0, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (145, 'Syamtalira Aron', 20121, 11, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (146, 'Syamtalira Bayu', 23325, 0, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (147, 'Tanah Jambo Aye', 45472, 0, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (148, 'Tanah Luas', 25992, 0, 0, '2025-04-29 16:47:19', 1, 2023);
INSERT INTO `diabetes_data` VALUES (149, 'Tanah Pasir', 10418, 32, 0, '2025-04-29 16:47:19', 0, 2023);

-- ----------------------------
-- Table structure for region_coordinates
-- ----------------------------
DROP TABLE IF EXISTS `region_coordinates`;
CREATE TABLE `region_coordinates`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wilayah` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `latitude` decimal(10, 6) NOT NULL,
  `longitude` decimal(10, 6) NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  `updated_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0) ON UPDATE CURRENT_TIMESTAMP(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `wilayah`(`wilayah`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 79 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for region_mappings
-- ----------------------------
DROP TABLE IF EXISTS `region_mappings`;
CREATE TABLE `region_mappings`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `geojson_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `database_region` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `geojson_name`(`geojson_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of region_mappings
-- ----------------------------
INSERT INTO `region_mappings` VALUES (1, 'Banda Baro', 'Banda Baro');
INSERT INTO `region_mappings` VALUES (2, 'Dewantara', 'Dewantara');
INSERT INTO `region_mappings` VALUES (3, 'Muara Batu', 'Muara Batu');
INSERT INTO `region_mappings` VALUES (4, 'Nisam', 'Nisam');
INSERT INTO `region_mappings` VALUES (5, 'Nisam Antara', 'Nisam Antara');
INSERT INTO `region_mappings` VALUES (6, 'Sawang', 'Sawang');

-- ----------------------------
-- Table structure for user_settings
-- ----------------------------
DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings`  (
  `user_id` int(11) NOT NULL,
  `theme` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'light',
  `show_chart_index` tinyint(1) NULL DEFAULT 1,
  `show_chart_dashboard` tinyint(1) NULL DEFAULT 1,
  `default_sort` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'jumlah_penderita',
  `language` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'id',
  `show_map_index` tinyint(1) NULL DEFAULT 1,
  `map_default_zoom` int(11) NULL DEFAULT 5,
  `map_default_center` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '-2.5, 118',
  `map_default_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'penderita',
  PRIMARY KEY (`user_id`) USING BTREE,
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of user_settings
-- ----------------------------
INSERT INTO `user_settings` VALUES (1, 'light', 1, 1, 'jumlah_penderita', 'en', 1, 10, '4.9, 97.2', 'penderita');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT current_timestamp(0),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'admin', '$2a$12$eW5YG8gUoO45OOh0vF3OyONlsREKWhcqdkfehlHEbyb9eAdyMv/Du', '2024-10-11 09:02:11');

SET FOREIGN_KEY_CHECKS = 1;

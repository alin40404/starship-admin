<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {

        // 完整省市区请执行 database/sql/seed_areas.sql（3748 条 FastAdmin 数据）
        if (Area::query()->exists()) {
            return;
        }

        $rows = [
            ['id' => 1, 'pid' => 0, 'shortname' => '北京', 'name' => '北京市', 'mergename' => '中国,北京市', 'level' => 1, 'pinyin' => 'beijing', 'code' => '010', 'zip' => '100000', 'first' => 'B', 'lng' => '116.405285', 'lat' => '39.904989'],
            ['id' => 2, 'pid' => 0, 'shortname' => '浙江', 'name' => '浙江省', 'mergename' => '中国,浙江省', 'level' => 1, 'pinyin' => 'zhejiang', 'code' => '0571', 'zip' => '310000', 'first' => 'Z', 'lng' => '120.153576', 'lat' => '30.287459'],
            ['id' => 3, 'pid' => 0, 'shortname' => '广东', 'name' => '广东省', 'mergename' => '中国,广东省', 'level' => 1, 'pinyin' => 'guangdong', 'code' => '020', 'zip' => '510000', 'first' => 'G', 'lng' => '113.280637', 'lat' => '23.125178'],
            ['id' => 101, 'pid' => 2, 'shortname' => '杭州', 'name' => '杭州市', 'mergename' => '中国,浙江省,杭州市', 'level' => 2, 'pinyin' => 'hangzhou', 'code' => '0571', 'zip' => '310000', 'first' => 'H', 'lng' => '120.153576', 'lat' => '30.287459'],
            ['id' => 102, 'pid' => 1, 'shortname' => '东城', 'name' => '东城区', 'mergename' => '中国,北京市,东城区', 'level' => 3, 'pinyin' => 'dongcheng', 'code' => '010', 'zip' => '100010', 'first' => 'D', 'lng' => '116.418757', 'lat' => '39.917544'],
            ['id' => 103, 'pid' => 1, 'shortname' => '西城', 'name' => '西城区', 'mergename' => '中国,北京市,西城区', 'level' => 3, 'pinyin' => 'xicheng', 'code' => '010', 'zip' => '100032', 'first' => 'X', 'lng' => '116.366794', 'lat' => '39.915309'],
            ['id' => 201, 'pid' => 2, 'shortname' => '宁波', 'name' => '宁波市', 'mergename' => '中国,浙江省,宁波市', 'level' => 2, 'pinyin' => 'ningbo', 'code' => '0574', 'zip' => '315000', 'first' => 'N', 'lng' => '121.549792', 'lat' => '29.868388'],
            ['id' => 202, 'pid' => 101, 'shortname' => '西湖', 'name' => '西湖区', 'mergename' => '中国,浙江省,杭州市,西湖区', 'level' => 3, 'pinyin' => 'xihu', 'code' => '0571', 'zip' => '310013', 'first' => 'X', 'lng' => '120.130203', 'lat' => '30.259244'],
            ['id' => 301, 'pid' => 3, 'shortname' => '广州', 'name' => '广州市', 'mergename' => '中国,广东省,广州市', 'level' => 2, 'pinyin' => 'guangzhou', 'code' => '020', 'zip' => '510000', 'first' => 'G', 'lng' => '113.280637', 'lat' => '23.125178'],
            ['id' => 302, 'pid' => 3, 'shortname' => '深圳', 'name' => '深圳市', 'mergename' => '中国,广东省,深圳市', 'level' => 2, 'pinyin' => 'shenzhen', 'code' => '0755', 'zip' => '518000', 'first' => 'S', 'lng' => '114.085947', 'lat' => '22.547000'],
        ];

        foreach ($rows as $row) {
            Area::query()->create($row);
        }
        }
}

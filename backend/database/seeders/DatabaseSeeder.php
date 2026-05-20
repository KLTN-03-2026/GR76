<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\VaiTro;
use App\Models\NguoiDung;
use App\Models\LoaiSuCo;
use App\Models\MucDoKhanCap;
use App\Models\SuCo;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ────────────────────────────────────────────────────
        VaiTro::insert([
            ['ten_vai_tro' => 'admin'],
            ['ten_vai_tro' => 'user'],
        ]);

        // ── Admins ───────────────────────────────────────────────────
        $admins = [
            ['ten_dang_nhap'=>'admin1','ho_ten'=>'Nguyễn Quản Trị','email'=>'admin1@sos.vn','so_dien_thoai'=>'0901111111', 'mat_khau' => Hash::make('password')],
            ['ten_dang_nhap'=>'admin2','ho_ten'=>'Trần Điều Hành','email'=>'admin2@sos.vn','so_dien_thoai'=>'0902222222', 'mat_khau' => Hash::make('password')],
            ['ten_dang_nhap'=>'superadmin','ho_ten'=>'Admin Trưởng','email'=>'super@sos.vn','so_dien_thoai'=>'0909999999', 'mat_khau' => Hash::make('password')],
            ['ten_dang_nhap'=>'minhquan@gmail.com','ho_ten'=>'Minh Quân (Email)','email'=>'minhquan@gmail.com','so_dien_thoai'=>'0909991810', 'mat_khau' => Hash::make('quan1810')],
        ];
        foreach ($admins as $a) {
            Admin::create($a);
        }

        // ── Admin minhquan1810 ────────────────────────────────────────
        $this->call(AdminMinhQuanSeeder::class);

        // ── Users ────────────────────────────────────────────────────
        $users = [
            ['ten'=>'Nguyễn Văn An',   'email'=>'an.nguyen@gmail.com',   'so_dien_thoai'=>'0901234561'],
            ['ten'=>'Trần Thị Bình',   'email'=>'binh.tran@gmail.com',   'so_dien_thoai'=>'0901234562'],
            ['ten'=>'Lê Văn Cường',    'email'=>'cuong.le@gmail.com',    'so_dien_thoai'=>'0901234563'],
            ['ten'=>'Phạm Thị Dung',   'email'=>'dung.pham@gmail.com',   'so_dien_thoai'=>'0901234564'],
            ['ten'=>'Hoàng Văn Em',    'email'=>'em.hoang@gmail.com',    'so_dien_thoai'=>'0901234565'],
            ['ten'=>'Vũ Thị Phương',   'email'=>'phuong.vu@gmail.com',   'so_dien_thoai'=>'0901234566'],
            ['ten'=>'Đặng Văn Giang',  'email'=>'giang.dang@gmail.com',  'so_dien_thoai'=>'0901234567'],
            ['ten'=>'Bùi Thị Hoa',     'email'=>'hoa.bui@gmail.com',     'so_dien_thoai'=>'0901234568'],
            ['ten'=>'Đỗ Văn Hùng',     'email'=>'hung.do@gmail.com',     'so_dien_thoai'=>'0901234569'],
            ['ten'=>'Ngô Thị Lan',     'email'=>'lan.ngo@gmail.com',     'so_dien_thoai'=>'0901234570'],
            ['ten'=>'Lý Văn Minh',     'email'=>'minh.ly@gmail.com',     'so_dien_thoai'=>'0901234571'],
            ['ten'=>'Dương Thị Na',    'email'=>'na.duong@gmail.com',    'so_dien_thoai'=>'0901234572'],
            ['ten'=>'Phan Văn Oanh',   'email'=>'oanh.phan@gmail.com',   'so_dien_thoai'=>'0901234573'],
            ['ten'=>'Cao Thị Phúc',    'email'=>'phuc.cao@gmail.com',    'so_dien_thoai'=>'0901234574'],
            ['ten'=>'Đinh Văn Quân',   'email'=>'quan.dinh@gmail.com',   'so_dien_thoai'=>'0901234575'],
            ['ten'=>'Tô Thị Rằng',    'email'=>'rang.to@gmail.com',     'so_dien_thoai'=>'0901234576'],
            ['ten'=>'Mai Văn Sơn',     'email'=>'son.mai@gmail.com',     'so_dien_thoai'=>'0901234577'],
            ['ten'=>'Trương Thị Thu',  'email'=>'thu.truong@gmail.com',  'so_dien_thoai'=>'0901234578'],
            ['ten'=>'Hồ Văn Uy',       'email'=>'uy.ho@gmail.com',       'so_dien_thoai'=>'0901234579'],
            ['ten'=>'Kim Thị Vân',     'email'=>'van.kim@gmail.com',     'so_dien_thoai'=>'0901234580'],
            ['ten'=>'Trần Văn Xuân',   'email'=>'xuan.tran@gmail.com',   'so_dien_thoai'=>'0901234581'],
            ['ten'=>'Nguyễn Thị Yên',  'email'=>'yen.nguyen@gmail.com',  'so_dien_thoai'=>'0901234582'],
            ['ten'=>'Lê Văn Zũng',     'email'=>'zung.le@gmail.com',     'so_dien_thoai'=>'0901234583'],
            ['ten'=>'test.user',        'email'=>'user@test.com',         'so_dien_thoai'=>'0900000001'],
            ['ten'=>'Demo User',        'email'=>'demo@sos.vn',           'so_dien_thoai'=>'0900000002'],
        ];
        foreach ($users as $u) {
            NguoiDung::create(array_merge($u, ['mat_khau'=>Hash::make('password'), 'id_vai_tro'=>2]));
        }

        // ── Categories ───────────────────────────────────────────────
        $categories = ['Tai nạn giao thông', 'Cháy', 'Cây đổ', 'Ngập nước', 'Khác'];
        foreach ($categories as $cat) {
            LoaiSuCo::create(['ten_loai' => $cat]);
        }

        // ── Levels ───────────────────────────────────────────────────
        $levels = [
            ['ten_muc_do'=>'Thấp',      'do_uu_tien'=>1],
            ['ten_muc_do'=>'Trung bình','do_uu_tien'=>2],
            ['ten_muc_do'=>'Cao',       'do_uu_tien'=>3],
            ['ten_muc_do'=>'Khẩn cấp', 'do_uu_tien'=>4],
        ];
        foreach ($levels as $lv) {
            MucDoKhanCap::create($lv);
        }

        // ── Realistic Incidents in Ho Chi Minh City ──────────────────
        $incidents = [
            // Tai nạn giao thông
            ['id_nguoi_dung'=>1,'tieu_de'=>'Tai nạn xe máy tại ngã tư Bình Phước','noi_dung'=>'Hai xe máy va chạm mạnh tại ngã tư, một người bị thương nhẹ, giao thông ùn tắc kéo dài 200m.','dia_chi'=>'Ngã tư Bình Phước, Q.Thủ Đức, TP.HCM','vi_do'=>10.8524,'kinh_do'=>106.7618,'id_loai_su_co'=>1,'id_muc_do'=>2,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>2,'tieu_de'=>'Ô tô tải lật trên cầu Sài Gòn','noi_dung'=>'Xe tải lớn bị lật ngửa trên cầu Sài Gòn hướng Q1, chắn toàn bộ làn đường. Cần hỗ trợ khẩn cấp.','dia_chi'=>'Cầu Sài Gòn, Q.Bình Thạnh, TP.HCM','vi_do'=>10.8085,'kinh_do'=>106.7320,'id_loai_su_co'=>1,'id_muc_do'=>4,'trang_thai'=>'in_progress'],
            ['id_nguoi_dung'=>3,'tieu_de'=>'Va chạm xe buýt và xe máy đường Điện Biên Phủ','noi_dung'=>'Xe buýt số 19 đụng xe máy khiến người đi xe máy ngã ra đường, đang chờ cấp cứu.','dia_chi'=>'219 Điện Biên Phủ, Q.Bình Thạnh, TP.HCM','vi_do'=>10.7982,'kinh_do'=>106.7172,'id_loai_su_co'=>1,'id_muc_do'=>3,'trang_thai'=>'pending'],
            ['id_nguoi_dung'=>4,'tieu_de'=>'Kẹt xe nghiêm trọng đường Võ Văn Kiệt','noi_dung'=>'Tai nạn liên hoàn 3 xe ô tô khiến đường Võ Văn Kiệt kẹt cứng từ vòng xoay Cống Quỳnh đến hầm Thủ Thiêm.','dia_chi'=>'Đường Võ Văn Kiệt, Q.1, TP.HCM','vi_do'=>10.7618,'kinh_do'=>106.6990,'id_loai_su_co'=>1,'id_muc_do'=>3,'trang_thai'=>'in_progress'],
            ['id_nguoi_dung'=>5,'tieu_de'=>'Xe máy tông vào dải phân cách Nguyễn Văn Linh','noi_dung'=>'Người điều khiển xe máy tông thẳng vào dải phân cách, có thể do ngủ gật. Đang nằm bất tỉnh trên đường.','dia_chi'=>'KCN Tân Thuận, Đường Nguyễn Văn Linh, Q.7, TP.HCM','vi_do'=>10.7276,'kinh_do'=>106.7168,'id_loai_su_co'=>1,'id_muc_do'=>4,'trang_thai'=>'resolved'],

            // Cháy
            ['id_nguoi_dung'=>6,'tieu_de'=>'Cháy nhà dân tại hẻm 58 Bình Thới','noi_dung'=>'Nhà 2 tầng bốc cháy dữ dội, nghi do chập điện. Lửa đang lan sang nhà kế bên, dân trong hẻm đã di tản.','dia_chi'=>'Hẻm 58 Bình Thới, Q.11, TP.HCM','vi_do'=>10.7630,'kinh_do'=>106.6672,'id_loai_su_co'=>2,'id_muc_do'=>4,'trang_thai'=>'in_progress'],
            ['id_nguoi_dung'=>7,'tieu_de'=>'Cháy nhà kho KCN Vĩnh Lộc','noi_dung'=>'Kho chứa hàng hóa bốc cháy, khói đen bốc cao. Lính cứu hỏa đã có mặt nhưng lửa vẫn chưa được khống chế.','dia_chi'=>'KCN Vĩnh Lộc, Q.Bình Chánh, TP.HCM','vi_do'=>10.7844,'kinh_do'=>106.5931,'id_loai_su_co'=>2,'id_muc_do'=>4,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>8,'tieu_de'=>'Bốc khói từ tòa nhà chung cư Ehome 3','noi_dung'=>'Khói đen bốc ra từ tầng 5 khu chung cư, cư dân đang tự sơ tán. Chưa rõ nguyên nhân, lửa chưa bùng phát lớn.','dia_chi'=>'Ehome 3, Bình Tân, TP.HCM','vi_do'=>10.7501,'kinh_do'=>106.6012,'id_loai_su_co'=>2,'id_muc_do'=>3,'trang_thai'=>'pending'],
            ['id_nguoi_dung'=>9,'tieu_de'=>'Cháy xe máy trên đường Cộng Hòa','noi_dung'=>'Xe máy bốc cháy dữ dội sau va chạm, lửa đang lan rộng, có nguy cơ gây cháy xe bên cạnh.','dia_chi'=>'278 Cộng Hòa, Q.Tân Bình, TP.HCM','vi_do'=>10.8012,'kinh_do'=>106.6603,'id_loai_su_co'=>2,'id_muc_do'=>3,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>10,'tieu_de'=>'Cháy rừng phòng hộ Cần Giờ','noi_dung'=>'Đám cháy lớn bùng phát tại khu rừng phòng hộ Cần Giờ, gió mạnh khiến lửa lan nhanh trên diện tích khoảng 2 hecta.','dia_chi'=>'Rừng phòng hộ Cần Giờ, TP.HCM','vi_do'=>10.4229,'kinh_do'=>106.9431,'id_loai_su_co'=>2,'id_muc_do'=>4,'trang_thai'=>'in_progress'],

            // Cây đổ
            ['id_nguoi_dung'=>11,'tieu_de'=>'Cây xanh lớn đổ chắn đường Nguyễn Hữu Cảnh','noi_dung'=>'Cây bàng cổ thụ đường kính hơn 1m bật gốc sau trận mưa lớn, đổ chắn hoàn toàn một chiều đường Nguyễn Hữu Cảnh.','dia_chi'=>'Đường Nguyễn Hữu Cảnh, Q.Bình Thạnh, TP.HCM','vi_do'=>10.7905,'kinh_do'=>106.7221,'id_loai_su_co'=>3,'id_muc_do'=>3,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>12,'tieu_de'=>'Cây đổ đè lên ô tô trên đường Lê Lợi','noi_dung'=>'Cây phượng vĩ gãy ngang thân đổ xuống đè bẹp 1 ô tô đậu bên lề. Tài xế may mắn không có trong xe.','dia_chi'=>'Đường Lê Lợi, Q.1, TP.HCM','vi_do'=>10.7750,'kinh_do'=>106.7008,'id_loai_su_co'=>3,'id_muc_do'=>2,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>13,'tieu_de'=>'Cành cây lớn rơi xuống đường sau cơn bão','noi_dung'=>'Nhiều cành cây gãy sau bão số 3 rơi xuống rải rác trên đoạn đường Trường Chinh, cản trở lưu thông.','dia_chi'=>'Đường Trường Chinh, Q.Tân Bình, TP.HCM','vi_do'=>10.8154,'kinh_do'=>106.6484,'id_loai_su_co'=>3,'id_muc_do'=>2,'trang_thai'=>'pending'],
            ['id_nguoi_dung'=>14,'tieu_de'=>'Cây đổ vào nhà dân Gò Vấp','noi_dung'=>'Cây xà cừ cổ thụ bật gốc sau giông lớn, đổ nghiêng vào mái nhà dân, gây hỏng nặng phần mái và trần nhà.','dia_chi'=>'152 Phan Văn Trị, Q.Gò Vấp, TP.HCM','vi_do'=>10.8382,'kinh_do'=>106.6845,'id_loai_su_co'=>3,'id_muc_do'=>3,'trang_thai'=>'in_progress'],

            // Ngập nước
            ['id_nguoi_dung'=>15,'tieu_de'=>'Ngập nặng đường Nguyễn Hữu Cảnh sau mưa lớn','noi_dung'=>'Đường Nguyễn Hữu Cảnh ngập sâu 50-70cm sau trận mưa lớn kéo dài 2 giờ. Nhiều xe máy chết máy giữa đường.','dia_chi'=>'Đường Nguyễn Hữu Cảnh, Q.Bình Thạnh, TP.HCM','vi_do'=>10.7897,'kinh_do'=>106.7193,'id_loai_su_co'=>4,'id_muc_do'=>3,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>16,'tieu_de'=>'Ngập lụt nghiêm trọng khu dân cư Bình Chánh','noi_dung'=>'Triều cường kết hợp mưa lớn gây ngập lụt toàn bộ khu dân cư xã Bình Hưng. Nước vào nhà sâu 1m, nhiều gia đình phải sơ tán khẩn cấp.','dia_chi'=>'Xã Bình Hưng, H.Bình Chánh, TP.HCM','vi_do'=>10.7018,'kinh_do'=>106.6342,'id_loai_su_co'=>4,'id_muc_do'=>4,'trang_thai'=>'in_progress'],
            ['id_nguoi_dung'=>17,'tieu_de'=>'Ngập tại hầm chui An Sương','noi_dung'=>'Hầm chui An Sương ngập nước sau mưa, nhiều xe máy và ô tô chết máy trong hầm, giao thông ùn tắc nghiêm trọng.','dia_chi'=>'Hầm chui An Sương, Q.12, TP.HCM','vi_do'=>10.8651,'kinh_do'=>106.6282,'id_loai_su_co'=>4,'id_muc_do'=>3,'trang_thai'=>'pending'],
            ['id_nguoi_dung'=>18,'tieu_de'=>'Sạt lở đất tại đường ven sông Quận 9','noi_dung'=>'Một đoạn đường ven sông dài khoảng 15m bị sạt lở sau mưa lớn. Phần đường sụt xuống sâu 2-3m, nguy hiểm cho người đi lại.','dia_chi'=>'Đường ven sông Long Phước, Q.9, TP.HCM','vi_do'=>10.8471,'kinh_do'=>106.8234,'id_loai_su_co'=>4,'id_muc_do'=>4,'trang_thai'=>'in_progress'],

            // Khác (Y tế, An ninh)
            ['id_nguoi_dung'=>19,'tieu_de'=>'Người đàn ông ngất xỉu giữa đường Lê Văn Sỹ','noi_dung'=>'Một người đàn ông khoảng 50 tuổi đột ngột ngã xuống đường, bất tỉnh. Người dân đang sơ cứu, cần xe cứu thương gấp.','dia_chi'=>'156 Lê Văn Sỹ, Q.Phú Nhuận, TP.HCM','vi_do'=>10.7944,'kinh_do'=>106.6823,'id_loai_su_co'=>5,'id_muc_do'=>4,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>20,'tieu_de'=>'Trộm cắp xe máy tại bãi giữ xe bệnh viện Chợ Rẫy','noi_dung'=>'Phát hiện tên trộm đang cạy khóa xe máy tại bãi giữ xe tầng 2 bệnh viện Chợ Rẫy, mặc áo đen, đội mũ bảo hiểm đen.','dia_chi'=>'BV Chợ Rẫy, 201B Nguyễn Chí Thanh, Q.5, TP.HCM','vi_do'=>10.7557,'kinh_do'=>106.6649,'id_loai_su_co'=>5,'id_muc_do'=>2,'trang_thai'=>'pending'],
            ['id_nguoi_dung'=>21,'tieu_de'=>'Nhóm thanh niên đánh nhau tại công viên Tao Đàn','noi_dung'=>'Khoảng 6-7 thanh niên đang ẩu đả tại công viên Tao Đàn, có hung khí. Người dân xung quanh hoảng sợ, cần can thiệp ngay.','dia_chi'=>'Công viên Tao Đàn, Q.1, TP.HCM','vi_do'=>10.7741,'kinh_do'=>106.6938,'id_loai_su_co'=>5,'id_muc_do'=>3,'trang_thai'=>'in_progress'],
            ['id_nguoi_dung'=>22,'tieu_de'=>'Ngộ độc thực phẩm tại bữa tiệc đám cưới Q.Bình Tân','noi_dung'=>'Hơn 20 người có biểu hiện ngộ độc sau bữa tiệc cưới tại nhà hàng. Triệu chứng: buồn nôn, đau bụng, tiêu chảy. Cần hỗ trợ y tế khẩn cấp.','dia_chi'=>'Nhà hàng An Phú, Q.Bình Tân, TP.HCM','vi_do'=>10.7531,'kinh_do'=>106.6041,'id_loai_su_co'=>5,'id_muc_do'=>4,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>23,'tieu_de'=>'Đuối nước tại hồ bơi tự phát Q.12','noi_dung'=>'Một học sinh khoảng 12 tuổi bị đuối nước khi tắm tại hồ nước tự phát trong khu đất trống, đang trong tình trạng nguy kịch.','dia_chi'=>'Đường Thạnh Lộc 29, Q.12, TP.HCM','vi_do'=>10.8612,'kinh_do'=>106.6451,'id_loai_su_co'=>5,'id_muc_do'=>4,'trang_thai'=>'resolved'],
            ['id_nguoi_dung'=>24,'tieu_de'=>'Rò rỉ khí gas tại chung cư Tân Phú','noi_dung'=>'Mùi gas nồng nặc từ tầng 3 chung cư lan ra hành lang và các tầng lân cận. Một số cư dân bị chóng mặt. Đã tắt nguồn điện.','dia_chi'=>'Chung cư Bình Minh, Q.Tân Phú, TP.HCM','vi_do'=>10.7893,'kinh_do'=>106.6208,'id_loai_su_co'=>5,'id_muc_do'=>3,'trang_thai'=>'in_progress'],
            ['id_nguoi_dung'=>25,'tieu_de'=>'Cướp giật điện thoại trên đường Phạm Ngọc Thạch','noi_dung'=>'Tên cướp trên xe máy Dream màu đen BKS 59A-XXX giật điện thoại của nữ sinh rồi tháo chạy về hướng Q.3.','dia_chi'=>'Đường Phạm Ngọc Thạch, Q.3, TP.HCM','vi_do'=>10.7842,'kinh_do'=>106.6949,'id_loai_su_co'=>5,'id_muc_do'=>3,'trang_thai'=>'pending'],
        ];

        foreach ($incidents as $inc) {
            SuCo::create($inc);
        }
    }
}

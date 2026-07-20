<?php

return [
    'leave' => [
        'requested' => [
            'title' => 'Đã gửi đơn nghỉ phép',
            'body' => 'Đơn nghỉ phép của bạn đã được gửi và đang chờ duyệt.',
        ],
        'pending_approval' => [
            'title' => 'Đơn nghỉ cần duyệt',
            'body' => 'Một thành viên trong nhóm đã gửi đơn nghỉ phép cần bạn xem xét.',
        ],
        'approved' => [
            'title' => 'Đơn nghỉ đã được duyệt',
            'body' => 'Đơn nghỉ phép của bạn đã được phê duyệt.',
        ],
        'rejected' => [
            'title' => 'Đơn nghỉ bị từ chối',
            'body' => 'Đơn nghỉ phép của bạn đã bị từ chối.',
        ],
        'cancelled' => [
            'title' => 'Đã hủy đơn nghỉ',
            'body' => 'Đơn nghỉ phép của bạn đã được hủy.',
        ],
        'cancelled_pending' => [
            'title' => 'Đơn nghỉ đã bị hủy',
            'body' => 'Một đơn nghỉ đang chờ bạn duyệt đã bị hủy.',
        ],
    ],
    'attendance' => [
        'correction_requested' => [
            'title' => 'Yêu cầu hiệu chỉnh chấm công',
            'body' => 'Có yêu cầu hiệu chỉnh chấm công cần bạn xem xét.',
        ],
        'correction_approved' => [
            'title' => 'Hiệu chỉnh chấm công đã được duyệt',
            'body' => 'Yêu cầu hiệu chỉnh chấm công của bạn đã được phê duyệt.',
        ],
        'correction_rejected' => [
            'title' => 'Hiệu chỉnh chấm công bị từ chối',
            'body' => 'Yêu cầu hiệu chỉnh chấm công của bạn đã bị từ chối.',
        ],
    ],
    'shift' => [
        'assigned' => [
            'title' => 'Đã gán ca làm việc',
            'body' => 'Bạn đã được gán một ca làm việc mới.',
        ],
        'changed' => [
            'title' => 'Ca làm việc đã cập nhật',
            'body' => 'Phân ca của bạn đã được cập nhật.',
        ],
    ],
    'asset' => [
        'assigned' => [
            'title' => 'Đã giao tài sản',
            'body' => 'Một tài sản công ty đã được giao cho bạn.',
        ],
        'returned' => [
            'title' => 'Đã trả tài sản',
            'body' => 'Tài sản được giao cho bạn đã được ghi nhận trả lại.',
        ],
    ],
    'payroll' => [
        'salary_changed' => [
            'title' => 'Đã cập nhật lương',
            'body' => 'Thông tin thu nhập của bạn đã được cập nhật.',
        ],
        'calculated' => [
            'title' => 'Bảng lương sẵn sàng duyệt',
            'body' => 'Một kỳ lương đã được tính và chờ phê duyệt.',
        ],
        'approved' => [
            'title' => 'Bảng lương đã duyệt',
            'body' => 'Một kỳ lương đã được phê duyệt và có thể chốt.',
        ],
        'finalized' => [
            'title' => 'Phiếu lương đã sẵn sàng',
            'body' => 'Phiếu lương của kỳ lương mới nhất đã sẵn sàng để xem.',
        ],
    ],
    'performance' => [
        'cycle_started' => [
            'title' => 'Đã bắt đầu kỳ đánh giá',
            'body' => 'Kỳ đánh giá hiệu suất bạn tham gia đã bắt đầu.',
        ],
        'cycle_finalized' => [
            'title' => 'Đã chốt kỳ đánh giá',
            'body' => 'Kỳ đánh giá hiệu suất bạn tham gia đã được chốt.',
        ],
        'evaluation_submitted' => [
            'title' => 'Đã gửi đánh giá',
            'body' => 'Một đánh giá hiệu suất đã được gửi và có thể cần bạn xem.',
        ],
    ],
    'onboarding' => [
        'started' => [
            'title' => 'Đã bắt đầu nhận việc',
            'body' => 'Hồ sơ nhận việc của bạn đã bắt đầu. Vui lòng hoàn thành các công việc được giao.',
        ],
        'completed' => [
            'title' => 'Đã hoàn tất nhận việc',
            'body' => 'Bạn đã hoàn tất nhận việc. Chào mừng bạn!',
        ],
        'task_completed' => [
            'title' => 'Đã hoàn thành việc nhận việc',
            'body' => 'Một mục trong checklist nhận việc đã được hoàn thành.',
        ],
    ],
    'employee' => [
        'created' => [
            'title' => 'Chào mừng',
            'body' => 'Hồ sơ nhân viên của bạn đã được tạo.',
        ],
        'created_hr' => [
            'title' => 'Nhân viên mới',
            'body' => 'Một hồ sơ nhân viên mới đã được thêm vào công ty.',
        ],
        'status_changed' => [
            'title' => 'Trạng thái nhân sự đã cập nhật',
            'body' => 'Trạng thái làm việc đã được ghi nhận thay đổi.',
        ],
    ],
    'report' => [
        'export_ready' => [
            'title' => 'Xuất báo cáo sẵn sàng',
            'body' => 'File xuất báo cáo của bạn đã sẵn sàng để tải xuống.',
        ],
    ],
    'recruitment' => [
        'offer_sent' => [
            'title' => 'Đã gửi offer',
            'body' => 'Một offer đã được gửi tới ứng viên.',
        ],
        'offer_accepted' => [
            'title' => 'Offer đã được chấp nhận',
            'body' => 'Một ứng viên đã chấp nhận offer.',
        ],
        'stage_changed' => [
            'title' => 'Cập nhật giai đoạn ứng viên',
            'body' => 'Một ứng viên đã chuyển sang giai đoạn mới.',
        ],
    ],
    'document' => [
        'shared' => [
            'title' => 'Tài liệu được chia sẻ với bạn',
            'body' => 'Một tài liệu đã được tải lên và chia sẻ với bạn.',
        ],
        'uploaded' => [
            'title' => 'Tài liệu nhạy cảm được tải lên',
            'body' => 'Một tài liệu nhạy cảm đã được tải lên và có thể cần xem xét.',
        ],
    ],
    'broadcast' => [
        'announcement' => [
            'title' => 'Thông báo công ty',
            'body' => 'Bạn có thông báo mới từ công ty.',
        ],
    ],
];

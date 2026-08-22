<div id="hotelChat" class="hotel-chat">
    <button type="button" id="hotelChatToggle" class="hotel-chat-toggle" aria-label="Mở tư vấn">
        <i class="bx bx-message-rounded-dots"></i>
        <span>Tư vấn</span>

        <span id="hotelChatBadge" class="hotel-chat-badge d-none">
            1
        </span>
    </button>

    <section id="hotelChatPanel" class="hotel-chat-panel" aria-hidden="true">
        <header class="hotel-chat-head">
            <div>
                <strong>MCuong Hotel</strong>
                <small>Hỗ trợ trực tuyến</small>
            </div>
            <div class="d-flex gap-1">
                <button type="button" id="hotelChatHide" class="btn btn-sm btn-light" title="Thu nhỏ">
                    <i class="bx bx-x"></i>
                </button>
            </div>
        </header>

        <div id="hotelChatOlderWrap" class="hotel-chat-older d-none">
            <button type="button" id="hotelChatLoadOlder" class="btn btn-sm btn-light border">
                Tải tin nhắn cũ hơn
            </button>
        </div>

        <div id="hotelChatMessages" class="hotel-chat-messages">
            <div class="hotel-chat-empty">
                Xin chào! Bạn cần khách sạn hỗ trợ vấn đề gì?
            </div>
        </div>

        <div id="hotelChatFilesPreview" class="hotel-chat-files-preview"></div>

        <form id="hotelChatForm" class="hotel-chat-form" enctype="multipart/form-data">
            @csrf
            <label class="hotel-chat-file-btn" for="hotelChatFiles" title="Chọn ảnh hoặc tài liệu">
                <i class="bx bx-paperclip"></i>
            </label>

            <input id="hotelChatFiles" name="files[]" type="file" class="d-none" multiple
                accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">

            <button type="button" class="hotel-chat-file-btn js-open-camera" data-target-input="#hotelChatCamera" title="Chụp ảnh bằng camera" aria-label="Chụp ảnh bằng camera">
                <i class="bx bx-camera"></i>
            </button>

            <input id="hotelChatCamera" name="camera_image" type="file" class="d-none" accept="image/jpeg,image/png,image/webp">
            <textarea id="hotelChatInput" name="message" rows="1" maxlength="2000"
                placeholder="Nhập tin nhắn..."></textarea>
            <button type="submit" class="hotel-chat-send" title="Gửi">
                <i class="bx bx-send"></i>
            </button>
        </form>
    </section>
</div>

<style>
    .hotel-chat {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 1080;
        font-family: inherit
    }

    .hotel-chat-toggle {
        height: 52px;
        min-width: 122px;
        padding: 0 20px;
        border: 0;
        border-radius: 999px;
        background: #0f5c96;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        box-shadow: 0 12px 28px rgba(15, 92, 150, 0.3);
        position: relative;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;

        cursor: pointer;
        transition:
            transform 0.18s ease,
            background 0.18s ease,
            box-shadow 0.18s ease;
    }

    .hotel-chat-toggle:hover {
        background: #0c4c7d;
        transform: translateY(-2px);
        box-shadow: 0 16px 34px rgba(15, 92, 150, 0.34);
    }

    .hotel-chat-toggle i {
        font-size: 21px;
    }

    .hotel-chat-badge {
        position: absolute;
        right: -2px;
        top: -2px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        border-radius: 99px;
        background: #dc2626;
        color: #fff;
        font-size: 11px;
        display: grid;
        place-items: center
    }

    .hotel-chat-panel {
        width: min(380px, calc(100vw - 28px));
        height: min(620px, calc(100vh - 110px));
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .2);
        display: none;
        flex-direction: column;
    }

    .hotel-chat-panel.is-open {
        display: flex;
    }

    .hotel-chat-toggle.is-hidden {
        display: none;
    }

    .hotel-chat-head {
        padding: 13px 15px;
        background: #2563eb;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between
    }

    .hotel-chat-head strong,
    .hotel-chat-head small {
        display: block
    }

    .hotel-chat-head small {
        opacity: .8;
        font-size: 11px
    }


    .hotel-chat-older {
        padding: 7px 12px;
        text-align: center;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .hotel-chat-messages {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 16px;
        background: #f5f7fb;
    }

    .hotel-chat-empty {
        text-align: center;
        color: #64748b;
        font-size: 13px;
        padding: 28px 12px
    }

    .hotel-chat-row {
        display: flex;
        margin-bottom: 12px
    }

    .hotel-chat-row.staff {
        justify-content: flex-start
    }

    .hotel-chat-row.customer {
        justify-content: flex-end
    }

    .hotel-chat-bubble {
        max-width: 82%;
        padding: 10px 12px;
        border-radius: 15px;
        font-size: 13px;
        line-height: 1.45;
        word-break: break-word
    }

    .hotel-chat-row.staff .hotel-chat-bubble {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-top-left-radius: 5px
    }

    .hotel-chat-row.customer .hotel-chat-bubble {
        background: #2563eb;
        color: #fff;
        border-top-right-radius: 5px
    }

    .hotel-chat-time {
        display: block;
        margin-top: 5px;
        font-size: 10px;
        opacity: .7
    }

    .hotel-chat-attachment {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-top: 7px;
        padding: 7px 8px;
        border-radius: 10px;
        background: rgba(255, 255, 255, .18);
        color: inherit;
        text-decoration: none
    }

    .hotel-chat-row.staff .hotel-chat-attachment {
        background: #f1f5f9;
        color: #334155
    }

    .hotel-chat-attachment img {
        width: 72px;
        height: 58px;
        border-radius: 8px;
        object-fit: cover
    }

    .hotel-chat-files-preview {
        padding: 0 12px;
        background: #fff;
        display: flex;
        gap: 8px;
        overflow-x: auto;
    }

    .hotel-chat-preview-item {
        position: relative;
        min-width: 92px;
        max-width: 118px;
        margin-top: 8px;
        padding: 7px 26px 7px 7px;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .hotel-chat-preview-item img {
        width: 38px;
        height: 38px;
        border-radius: 7px;
        object-fit: cover;
        flex: 0 0 auto;
    }

    .hotel-chat-preview-icon {
        font-size: 29px;
        color: #64748b;
        flex: 0 0 auto;
    }

    .hotel-chat-preview-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 11px;
        color: #334155;
    }

    .hotel-chat-preview-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 19px;
        height: 19px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: #dc2626;
        color: #fff;
        font-size: 15px;
        line-height: 19px;
        cursor: pointer;
    }

    .hotel-chat-form {
        padding: 11px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        align-items: flex-end;
        gap: 8px
    }

    .hotel-chat-form textarea {
        flex: 1;
        min-height: 42px;
        max-height: 100px;
        resize: none;
        border: 1px solid #dbe3ef;
        border-radius: 16px;
        padding: 10px 12px;
        outline: none
    }

    .hotel-chat-file-btn,
    .hotel-chat-send {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
    }

    .hotel-chat-file-btn {
        border: 0;
        padding: 0;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        font-size: 21px;
        transition:
            background 0.16s ease,
            color 0.16s ease;
    }

    .hotel-chat-file-btn:hover {
        background: #eef4fa;
        color: #0f5c96;
    }

    .hotel-chat-send {
        border: 0;
        border-radius: 50%;
        background: #0f5c96;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
    }

    @media(max-width:576px) {
        .hotel-chat {
            right: 14px;
            bottom: 14px
        }

        .hotel-chat-panel {
            height: calc(100vh - 92px)
        }
    }
</style>
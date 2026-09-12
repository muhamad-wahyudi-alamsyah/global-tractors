<?php
/**
 * Multi-file upload modal for the media library.
 *
 * Recovered during the R1 layout refactor: this markup sat after </main> in
 * the page template and was rendered inline there. It is a part now, requested
 * via gti_dashboard_close(['modals' => [...]]).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
    <div id="uploadModal" style="display:none; position:fixed; inset:0; z-index:2000; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:12px; padding:32px; max-width:480px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
            <h3 style="margin:0 0 8px; font-size:18px; font-weight:600; color:#1a1f36;">Upload Media</h3>
            <p style="margin:0 0 20px; font-size:13px; color:#6b7280;">Select files to upload to the media library</p>
            <div id="uploadDropZone" style="border:2px dashed #d1d5db; border-radius:10px; padding:40px 20px; text-align:center; cursor:pointer; transition: all 0.2s;">
                <i class="fas fa-cloud-upload-alt" style="font-size:36px; color:#9ca3af; margin-bottom:12px; display:block;"></i>
                <p style="margin:0; font-size:14px; color:#6b7280; font-weight:500;">Drag & drop files here</p>
                <p style="margin:4px 0 0; font-size:12px; color:#9ca3af;">or click to browse</p>
                <input type="file" id="fileInput" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" style="display:none;">
            </div>
            <div id="uploadProgress" style="margin-top:16px; display:none;">
                <div style="background:#f3f4f6; border-radius:6px; height:6px; overflow:hidden;">
                    <div id="uploadProgressBar" style="background:#F5A623; height:100%; width:0; transition:width 0.3s; border-radius:6px;"></div>
                </div>
                <p id="uploadStatus" style="margin:8px 0 0; font-size:12px; color:#6b7280; text-align:center;"></p>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:20px;">
                <button type="button" class="gti-drawer-btn" onclick="closeUploadModal()">Cancel</button>
                <button type="button" class="gti-drawer-btn gti-drawer-btn-primary" id="startUploadBtn" disabled>Upload</button>
            </div>
        </div>
    </div>

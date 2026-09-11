import { InitUserMgt } from "./user-mgt.js";
import { InitProfile } from "./profile.js";
import { InitTheme } from "./theme.js";
import { InitNotification } from "./notifikasi.js";
import { InitModifyCourse } from "./modifyCourse.js";
import { InitReleased } from "./released.js";
import { InitApproval } from "./approval.js";
import { InitAssignUser } from "./assignUser.js";
import { InitDetailCourse } from "./detailCourse.js";
import { InitCourseEnrollment } from "./courseEnrollment.js";
import { InitLearnerCourse } from "./learnerDashboard.js";
import { InitSubmissionCourse } from "./submissionCourse.js";
import { InitTrackLearner } from "./TrackLearner.js";


function safeInit(name, fn, ...args) {
    try {
        fn(...args);
    } catch (error) {
    }
}

document.addEventListener("DOMContentLoaded", function () {
    
    // Jalankan modul-modul dengan aman
    safeInit("UserMgt", InitUserMgt);
    safeInit("Profile", InitProfile, window.defaultProfileImgUrl);
    safeInit("Notification", InitNotification);
    safeInit("AssignUser", InitAssignUser);
    safeInit("Theme", InitTheme);
    safeInit("Approval", InitApproval);
    safeInit("DetailCourse", InitDetailCourse);
    safeInit("LearnerCourse", InitLearnerCourse);
    safeInit("CourseEnrollment", InitCourseEnrollment);
    safeInit("ModifyCourse", InitModifyCourse);
    safeInit("InitSubmissionCourse", InitSubmissionCourse);
    safeInit("InitTrackLearner", InitTrackLearner);
    safeInit("InitReleased", InitReleased);


    // ✅ Debug submit form Approval
    const form = document.getElementById("approvalForm");
    if (form) {
        form.addEventListener("submit", function (e) {
            const formData = new FormData(form);
            let output = {};

            formData.forEach((value, key) => {
                if (output[key]) {
                    if (!Array.isArray(output[key])) {
                        output[key] = [output[key]];
                    }
                    output[key].push(value);
                } else {
                    output[key] = value;
                }
            });

            // kalau sudah oke, tinggal uncomment:
            // form.submit();
        });
    }
});

import{z as $e,g as et,o as d,w as tt,e,a as r,s as Me,t as s,n as Xe,F as u,i as h,v as Ie,ad as st,h as f,f as it,r as Ve,a2 as at,c as Ze,B as ot,W as lt}from"./app-De5aCyOy.js";import{_ as dt}from"./AdminLayout.vue_vue_type_script_setup_true_lang-Dj8HCjtT.js";import{_ as rt}from"./_plugin-vue_export-helper-DsuA5ZjC.js";import"./AppLayout.vue_vue_type_script_setup_true_lang-DrTEAkGA.js";import"./Button.vue_vue_type_script_setup_true_lang-C1ptny9S.js";import"./index-D3npKzxP.js";import"./index-shR_F4Lk.js";import"./AppLogoIcon.vue_vue_type_script_setup_true_lang-UOeKMcHs.js";import"./clock-BXDw5ZYb.js";const nt={name:"MonthlyKpiDashboard",components:{AdminLayout:dt},props:{kpiData:{type:Object,required:!0},filterData:{type:Object,required:!0},currentFilters:{type:Object,required:!0},lastUpdated:{type:String,required:!0}},setup(o){const t=Ve(!1),i=Ve(!1),l=Ve(!1),_=at({month:o.currentFilters.month,year:o.currentFilters.year,department_id:o.currentFilters.department_id||"",course_id:o.currentFilters.course_id||""}),Be=Ze(()=>[{label:"Dashboard",href:route("dashboard")},{label:"Reports",href:route("admin.reports.index")},{label:"Monthly KPI Dashboard",href:null}]),Ue=async()=>{try{l.value=!0;const n=Re(),c=window.open("","screenshot","width=1200,height=800");if(!c)throw new Error("Popup blocked. Please allow popups for this site.");c.document.write(n),c.document.close(),await new Promise(b=>setTimeout(b,1e3));const g=c.document.createElement("script");g.src="https://html2canvas.hertzen.com/dist/html2canvas.min.js",c.document.head.appendChild(g),await new Promise(b=>{g.onload=b}),await new Promise(b=>setTimeout(b,500));const v=await c.html2canvas(c.document.body,{backgroundColor:"#ffffff",scale:1.5,useCORS:!0,allowTaint:!0,width:c.document.body.scrollWidth,height:c.document.body.scrollHeight}),m=document.createElement("a");m.download=`KPI_Report_${_.month}_${_.year}_${new Date().toISOString().split("T")[0]}.png`,m.href=v.toDataURL("image/png",.9),document.body.appendChild(m),m.click(),document.body.removeChild(m),c.close(),console.log("✅ Screenshot generated successfully!"),alert("📸 Screenshot downloaded successfully!")}catch(n){console.error("❌ Screenshot failed:",n),alert(`Screenshot failed: ${n.message}

Please try using your browser's built-in screenshot feature.`)}finally{l.value=!1}},Re=()=>{var n,c,g,v,m,b,y,w,D,C,P,S,T,z,A,R,N,E,F,O,L,j,M,U,I,V,B,q,K,W,Q,H,Y,G,J,X,Z,$,ee,te,se,ie,ae,oe,le,de,re,ne,ce,pe,ve,ge,me,be,xe,ue,he,_e,fe,ke,ye,we,De,Ce,Pe,Se,Te,ze,Ae;return`
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>KPI Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #ffffff;
            color: #212529;
            line-height: 1.6;
            padding: 40px;
        }

        .report-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 3px solid #dee2e6;
        }

        .company-logo {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .report-title {
            font-size: 36px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 10px;
        }

        .report-subtitle {
            font-size: 20px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .report-date {
            font-size: 14px;
            color: #868e96;
        }

        .kpi-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }

        .kpi-cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .kpi-card.blue {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .kpi-card.green {
            border-color: #28a745;
            background: #e8f5e8;
        }

        .kpi-card.purple {
            border-color: #6f42c1;
            background: #f3e5f5;
        }

        .kpi-card.orange {
            border-color: #fd7e14;
            background: #fff3e0;
        }

        .kpi-card.danger {
            border-color: #dc3545;
            background: #f8d7da;
        }

        .kpi-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .kpi-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .metric-row {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-label {
            font-weight: 500;
            color: #495057;
        }

        .metric-value {
            font-weight: 600;
            color: #212529;
            font-size: 18px;
        }

        .outcomes-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .outcome-card {
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .outcome-card.success {
            background: #d4edda;
            border-color: #28a745;
        }

        .outcome-card.danger {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .outcome-card.info {
            background: #d1ecf1;
            border-color: #17a2b8;
        }

        .outcome-label {
            font-size: 14px;
            color: #495057;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .outcome-value {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
        }

        .feedback-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .feedback-summary {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .feedback-card {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
        }

        .feedback-label {
            font-size: 14px;
            color: #495057;
            margin-bottom: 8px;
        }

        .feedback-value {
            font-size: 32px;
            font-weight: 700;
            color: #212529;
        }

        .sentiment-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .sentiment-item {
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .sentiment-item.positive {
            background: #d4edda;
            border-color: #28a745;
        }

        .sentiment-item.neutral {
            background: #f8f9fa;
            border-color: #6c757d;
        }

        .sentiment-item.negative {
            background: #f8d7da;
            border-color: #dc3545;
        }

        .sentiment-emoji {
            font-size: 24px;
        }

        .sentiment-text {
            font-weight: 500;
            color: #495057;
            font-size: 16px;
        }

        .tables-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .performance-table-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 15px;
        }

        .simple-table {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
        }

        .table-header {
            background: #e9ecef;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            font-weight: 600;
            color: #495057;
        }

        .header-cell {
            padding: 12px 16px;
            border-right: 1px solid #dee2e6;
            font-size: 14px;
        }

        .header-cell:last-child {
            border-right: none;
        }

        .table-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            border-top: 1px solid #e9ecef;
        }

        .table-cell {
            padding: 12px 16px;
            border-right: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            color: #495057;
            font-size: 14px;
        }

        .table-cell:last-child {
            border-right: none;
        }

        .trend-container {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 30px;
            align-items: center;
        }

        .trend-card {
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }

        .trend-card.current {
            background: #e3f2fd;
            border-color: #007bff;
        }

        .trend-label {
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 12px;
        }

        .trend-value {
            font-size: 32px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 8px;
        }

        .trend-period {
            font-size: 14px;
            color: #868e96;
        }

        .trend-arrow {
            text-align: center;
        }

        .arrow-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .trend-direction {
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            text-transform: capitalize;
        }

        .report-footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 3px solid #dee2e6;
            text-align: center;
        }

        .footer-content {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            display: inline-block;
        }

        .footer-line {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .kpi-cards-grid, .outcomes-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .metrics-grid, .feedback-container, .tables-container {
                grid-template-columns: 1fr;
            }
            .trend-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="company-logo">🏢</div>
        <h1 class="report-title">Monthly Training KPI Report</h1>
        <div class="report-subtitle">${((n=o.kpiData.period)==null?void 0:n.period_name)||"Current Period"}</div>
        <div class="report-date">Generated on: ${new Date().toLocaleDateString()}</div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Key Performance Indicators</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Courses Delivered</div>
                <div class="kpi-value">${((c=o.kpiData.delivery_overview)==null?void 0:c.courses_delivered)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Total Enrolled</div>
                <div class="kpi-value">${((g=o.kpiData.delivery_overview)==null?void 0:g.total_enrolled)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Active Participants</div>
                <div class="kpi-value">${((v=o.kpiData.delivery_overview)==null?void 0:v.active_participants)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((m=o.kpiData.delivery_overview)==null?void 0:m.completion_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">💻 Online Course Analytics</h2>
        <div class="kpi-cards-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="kpi-card blue">
                <div class="kpi-icon">💻</div>
                <div class="kpi-label">Online Courses</div>
                <div class="kpi-value">${((y=(b=o.kpiData.online_course_analytics)==null?void 0:b.delivery)==null?void 0:y.online_courses_delivered)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">📝</div>
                <div class="kpi-label">Enrollments</div>
                <div class="kpi-value">${((D=(w=o.kpiData.online_course_analytics)==null?void 0:w.delivery)==null?void 0:D.online_enrollments)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completed</div>
                <div class="kpi-value">${((P=(C=o.kpiData.online_course_analytics)==null?void 0:C.delivery)==null?void 0:P.online_completed)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">📊</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((T=(S=o.kpiData.online_course_analytics)==null?void 0:S.delivery)==null?void 0:T.online_completion_rate)||0}%</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon">👥</div>
                <div class="kpi-label">Active Learners</div>
                <div class="kpi-value">${((A=(z=o.kpiData.online_course_analytics)==null?void 0:z.delivery)==null?void 0:A.active_online_learners)||0}</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🎯 Engagement & Attendance</h2>
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-label">📋 Attendance Rate:</div>
                <div class="metric-value">${((R=o.kpiData.attendance_engagement)==null?void 0:R.average_attendance_rate)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">⏱️ Average Time Spent:</div>
                <div class="metric-value">${((N=o.kpiData.attendance_engagement)==null?void 0:N.average_time_spent)||0} hours</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">💯 Engagement Score:</div>
                <div class="metric-value">${((E=o.kpiData.attendance_engagement)==null?void 0:E.engagement_score)||0}%</div>
            </div>
            <div class="metric-row">
                <div class="metric-label">🕐 Clock Consistency:</div>
                <div class="metric-value">${((F=o.kpiData.attendance_engagement)==null?void 0:F.clocking_consistency)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🎥 Video Engagement Metrics</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">▶️</div>
                <div class="kpi-label">Videos Watched</div>
                <div class="kpi-value">${((L=(O=o.kpiData.online_course_analytics)==null?void 0:O.video_engagement)==null?void 0:L.total_videos_watched)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Avg Completion</div>
                <div class="kpi-value">${((M=(j=o.kpiData.online_course_analytics)==null?void 0:j.video_engagement)==null?void 0:M.avg_video_completion)||0}%</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">⏱️</div>
                <div class="kpi-label">Watch Time</div>
                <div class="kpi-value">${((I=(U=o.kpiData.online_course_analytics)==null?void 0:U.video_engagement)==null?void 0:I.total_watch_time_hours)||0}h</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">🔄</div>
                <div class="kpi-label">Replays</div>
                <div class="kpi-value">${((B=(V=o.kpiData.online_course_analytics)==null?void 0:V.video_engagement)==null?void 0:B.video_replay_count)||0}</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📈 Learning Outcomes</h2>
        <div class="outcomes-grid">
            <div class="outcome-card success">
                <div class="outcome-label">✅ Quiz Pass Rate</div>
                <div class="outcome-value">${((q=o.kpiData.learning_outcomes)==null?void 0:q.quiz_pass_rate)||0}%</div>
            </div>
            <div class="outcome-card danger">
                <div class="outcome-label">❌ Quiz Fail Rate</div>
                <div class="outcome-value">${((K=o.kpiData.learning_outcomes)==null?void 0:K.quiz_fail_rate)||0}%</div>
            </div>
            <div class="outcome-card info">
                <div class="outcome-label">📊 Average Score</div>
                <div class="outcome-value">${((W=o.kpiData.learning_outcomes)==null?void 0:W.average_quiz_score)||0}%</div>
            </div>
            <div class="outcome-card">
                <div class="outcome-label">📈 Improvement Rate</div>
                <div class="outcome-value">${((Q=o.kpiData.learning_outcomes)==null?void 0:Q.improvement_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📚 Online Module Progress</h2>
        <div class="kpi-cards-grid">
            <div class="kpi-card blue">
                <div class="kpi-icon">📚</div>
                <div class="kpi-label">Total Modules</div>
                <div class="kpi-value">${((Y=(H=o.kpiData.online_course_analytics)==null?void 0:H.module_progress)==null?void 0:Y.total_modules)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">✅</div>
                <div class="kpi-label">Completed</div>
                <div class="kpi-value">${((J=(G=o.kpiData.online_course_analytics)==null?void 0:G.module_progress)==null?void 0:J.completed_modules)||0}</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">👤</div>
                <div class="kpi-label">Avg Per User</div>
                <div class="kpi-value">${((Z=(X=o.kpiData.online_course_analytics)==null?void 0:X.module_progress)==null?void 0:Z.avg_modules_per_user)||0}</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">📈</div>
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value">${((ee=($=o.kpiData.online_course_analytics)==null?void 0:$.module_progress)==null?void 0:ee.module_completion_rate)||0}%</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">⭐ Course Quality & Feedback</h2>
        <div class="feedback-container">
            <div class="feedback-summary">
                <div class="feedback-card">
                    <div class="feedback-label">Average Rating</div>
                    <div class="feedback-value">${((te=o.kpiData.feedback_analysis)==null?void 0:te.average_rating)||0}/5</div>
                </div>
                <div class="feedback-card">
                    <div class="feedback-label">Total Feedback</div>
                    <div class="feedback-value">${((se=o.kpiData.feedback_analysis)==null?void 0:se.total_feedback_count)||0}</div>
                </div>
            </div>
            <div class="sentiment-container">
                <div class="sentiment-item positive">
                    <span class="sentiment-emoji">😊</span>
                    <span class="sentiment-text">Positive: ${((ae=(ie=o.kpiData.feedback_analysis)==null?void 0:ie.feedback_sentiment)==null?void 0:ae.positive)||0}%</span>
                </div>
                <div class="sentiment-item neutral">
                    <span class="sentiment-emoji">😐</span>
                    <span class="sentiment-text">Neutral: ${((le=(oe=o.kpiData.feedback_analysis)==null?void 0:oe.feedback_sentiment)==null?void 0:le.neutral)||0}%</span>
                </div>
                <div class="sentiment-item negative">
                    <span class="sentiment-emoji">😞</span>
                    <span class="sentiment-text">Negative: ${((re=(de=o.kpiData.feedback_analysis)==null?void 0:de.feedback_sentiment)==null?void 0:re.negative)||0}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">⏱️ Learning Session Analytics</h2>
        <div class="kpi-cards-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="kpi-card blue">
                <div class="kpi-icon">🎯</div>
                <div class="kpi-label">Total Sessions</div>
                <div class="kpi-value">${((ce=(ne=o.kpiData.online_course_analytics)==null?void 0:ne.session_analytics)==null?void 0:ce.total_sessions)||0}</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-icon">⏱️</div>
                <div class="kpi-label">Avg Duration</div>
                <div class="kpi-value">${((ve=(pe=o.kpiData.online_course_analytics)==null?void 0:pe.session_analytics)==null?void 0:ve.avg_session_duration_minutes)||0}m</div>
            </div>
            <div class="kpi-card purple">
                <div class="kpi-icon">👁️</div>
                <div class="kpi-label">Attention Score</div>
                <div class="kpi-value">${((me=(ge=o.kpiData.online_course_analytics)==null?void 0:ge.session_analytics)==null?void 0:me.avg_attention_score)||0}%</div>
            </div>
            <div class="kpi-card orange">
                <div class="kpi-icon">⏰</div>
                <div class="kpi-label">Learning Hours</div>
                <div class="kpi-value">${((xe=(be=o.kpiData.online_course_analytics)==null?void 0:be.session_analytics)==null?void 0:xe.total_learning_hours)||0}h</div>
            </div>
            <div class="kpi-card danger">
                <div class="kpi-icon">⚠️</div>
                <div class="kpi-label">Suspicious Activity</div>
                <div class="kpi-value">${((he=(ue=o.kpiData.online_course_analytics)==null?void 0:ue.session_analytics)==null?void 0:he.suspicious_activity_count)||0}</div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🏆 Performance Analysis</h2>
        <div class="tables-container">
            <div class="performance-table-container">
                <h3 class="table-title">🥇 Top-Performing Courses</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">Course Name</div>
                        <div class="header-cell">Rating</div>
                        <div class="header-cell">Completion</div>
                    </div>
                    ${(((_e=o.kpiData.performance_analysis)==null?void 0:_e.top_performing_courses)||[]).slice(0,5).map((p,x)=>`
                        <div class="table-row">
                            <div class="table-cell">${x+1}. ${p.name}</div>
                            <div class="table-cell">${p.rating}/5</div>
                            <div class="table-cell">${p.completion_rate}%</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
            <div class="performance-table-container">
                <h3 class="table-title">🌟 Top-Performing Users</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">User Name</div>
                        <div class="header-cell">Score</div>
                        <div class="header-cell">Completed</div>
                    </div>
                    ${(((fe=o.kpiData.performance_analysis)==null?void 0:fe.top_performing_users)||[]).slice(0,5).map((p,x)=>`
                        <div class="table-row">
                            <div class="table-cell">${x+1}. ${p.name}</div>
                            <div class="table-cell">${p.score}%</div>
                            <div class="table-cell">${p.courses_completed||0}</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">🏆 Online Course Top Performers</h2>
        <div class="tables-container">
            <div class="performance-table-container">
                <h3 class="table-title">🥇 Top Online Courses</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">Course Name</div>
                        <div class="header-cell">Completion</div>
                        <div class="header-cell">Enrolled</div>
                    </div>
                    ${(((ye=(ke=o.kpiData.online_course_analytics)==null?void 0:ke.top_performers)==null?void 0:ye.top_online_courses)||[]).slice(0,5).map((p,x)=>`
                        <div class="table-row">
                            <div class="table-cell">${x+1}. ${p.name}</div>
                            <div class="table-cell">${p.completion_rate}%</div>
                            <div class="table-cell">${p.total_enrolled||0}</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
            <div class="performance-table-container">
                <h3 class="table-title">🌟 Top Online Learners</h3>
                <div class="simple-table">
                    <div class="table-header">
                        <div class="header-cell">User Name</div>
                        <div class="header-cell">Completed</div>
                        <div class="header-cell">Progress</div>
                    </div>
                    ${(((De=(we=o.kpiData.online_course_analytics)==null?void 0:we.top_performers)==null?void 0:De.top_online_learners)||[]).slice(0,5).map((p,x)=>`
                        <div class="table-row">
                            <div class="table-cell">${x+1}. ${p.name}</div>
                            <div class="table-cell">${p.courses_completed}</div>
                            <div class="table-cell">${p.avg_progress}%</div>
                        </div>
                    `).join("")||'<div class="table-row"><div class="table-cell">No data available</div><div class="table-cell">-</div><div class="table-cell">-</div></div>'}
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-section">
        <h2 class="section-title">📊 Monthly Engagement Trend</h2>
        <div class="trend-container">
            <div class="trend-card current">
                <div class="trend-label">Current Month</div>
                <div class="trend-value">${((Ce=o.kpiData.engagement_trends)==null?void 0:Ce.current_month_engagement)||0}%</div>
                <div class="trend-period">${(Pe=o.kpiData.period)==null?void 0:Pe.period_name}</div>
            </div>
            <div class="trend-arrow">
                <div class="arrow-icon">${k((Se=o.kpiData.engagement_trends)==null?void 0:Se.trend_direction)}</div>
                <div class="trend-direction">${((Te=o.kpiData.engagement_trends)==null?void 0:Te.trend_direction)||"stable"}</div>
            </div>
            <div class="trend-card">
                <div class="trend-label">Previous Month</div>
                <div class="trend-value">${((ze=o.kpiData.engagement_trends)==null?void 0:ze.previous_month_engagement)||0}%</div>
                <div class="trend-period">${((Ae=o.kpiData.engagement_trends)==null?void 0:Ae.trend_percentage)||0}% change</div>
            </div>
        </div>
    </div>

</body>
</html>`},Ne=async()=>{t.value=!0;try{await lt.get(route("admin.reports.monthly-kpi"),Object.fromEntries(Object.entries(_).filter(([n,c])=>c!=="")),{preserveState:!0,preserveScroll:!0})}catch(n){console.error("Error applying filters:",n),alert("Error applying filters. Please try again.")}finally{t.value=!1}},Ee=()=>{window.location.reload()},Fe=()=>{try{t.value=!0;const n=new URLSearchParams;Object.entries(_).forEach(([g,v])=>{v!==""&&v!==null&&v!==void 0&&n.append(g,v)});const c=route("admin.reports.export-monthly-kpi-csv")+"?"+n.toString();window.open(c,"_blank")}catch(n){console.error("Error exporting CSV:",n),alert("Error exporting CSV. Please try again.")}finally{setTimeout(()=>{t.value=!1},1e3)}},Oe=n=>n?new Date(n).toLocaleString():"Unknown",Le=n=>{switch(n){case"up":return"trend-up";case"down":return"trend-down";default:return"trend-stable"}},k=n=>{switch(n){case"up":return"↗️";case"down":return"↘️";default:return"➡️"}},je=Ze(()=>o.kpiData&&Object.keys(o.kpiData).length>0);return ot(()=>{console.log("🎯 Monthly KPI Dashboard mounted")}),{loading:t,showFilters:i,filters:_,screenshotLoading:l,breadcrumbs:Be,applyFilters:Ne,refreshData:Ee,exportCsv:Fe,formatDateTime:Oe,getTrendClass:Le,getTrendArrow:k,generateDirectScreenshot:Ue,hasData:je}}},ct={class:"monthly-kpi-dashboard bg-black text-white min-h-screen dark"},pt={class:"dashboard-header bg-gray-800 border-b border-gray-600 p-4 sm:p-6"},vt={class:"header-content"},gt={class:"title-section"},mt={class:"period-display text-gray-300 mt-2"},bt={class:"header-actions flex flex-wrap gap-3 mt-4"},xt={class:"export-buttons flex gap-2"},ut=["disabled"],ht=["disabled"],_t=["disabled"],ft={class:"filter-panel bg-gray-700 border border-gray-600 rounded-lg p-4 mt-4"},kt={class:"filter-grid grid grid-cols-1 md:grid-cols-3 gap-4"},yt={class:"filter-group"},wt=["value"],Dt={class:"filter-group"},Ct=["value"],Pt={class:"filter-group"},St=["value"],Tt={class:"update-info mt-4 pt-4 border-t border-gray-600"},zt={class:"last-updated text-sm text-gray-300"},At={key:0,class:"loading-overlay fixed inset-0 bg-gray-900 bg-opacity-90 flex items-center justify-center z-50"},Rt={key:1,class:"dashboard-content p-4 sm:p-6 space-y-8"},Nt={class:"kpi-section delivery-overview"},Et={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},Ft={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Ot={class:"kpi-value text-3xl font-bold text-white"},Lt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},jt={class:"kpi-value text-3xl font-bold text-white"},Mt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Ut={class:"kpi-value text-3xl font-bold text-white"},It={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Vt={class:"kpi-value text-3xl font-bold text-white"},Bt={class:"kpi-section attendance-engagement"},qt={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},Kt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Wt={class:"kpi-value text-3xl font-bold text-white"},Qt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Ht={class:"kpi-value text-3xl font-bold text-white"},Yt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Gt={class:"kpi-value text-3xl font-bold text-white"},Jt={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Xt={class:"kpi-value text-3xl font-bold text-white"},Zt={class:"kpi-section online-course-analytics"},$t={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6"},es={class:"kpi-card bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20"},ts={class:"kpi-value text-3xl font-bold text-blue-300"},ss={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},is={class:"kpi-value text-3xl font-bold text-white"},as={class:"kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20"},os={class:"kpi-value text-3xl font-bold text-green-300"},ls={class:"kpi-card bg-gray-800 border border-purple-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-purple-900/20"},ds={class:"kpi-value text-3xl font-bold text-purple-300"},rs={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},ns={class:"kpi-value text-3xl font-bold text-white"},cs={class:"kpi-section video-engagement"},ps={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},vs={class:"kpi-card bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20"},gs={class:"kpi-value text-3xl font-bold text-blue-300"},ms={class:"kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20"},bs={class:"kpi-value text-3xl font-bold text-green-300"},xs={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},us={class:"kpi-value text-3xl font-bold text-white"},hs={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},_s={class:"kpi-value text-3xl font-bold text-white"},fs={class:"kpi-section learning-outcomes"},ks={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},ys={class:"kpi-card success bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20"},ws={class:"kpi-value text-3xl font-bold text-green-300"},Ds={class:"kpi-card danger bg-gray-800 border border-red-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-red-900/20"},Cs={class:"kpi-value text-3xl font-bold text-red-300"},Ps={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Ss={class:"kpi-value text-3xl font-bold text-white"},Ts={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},zs={class:"kpi-value text-3xl font-bold text-white"},As={class:"kpi-section module-progress"},Rs={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"},Ns={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Es={class:"kpi-value text-3xl font-bold text-white"},Fs={class:"kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20"},Os={class:"kpi-value text-3xl font-bold text-green-300"},Ls={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},js={class:"kpi-value text-3xl font-bold text-white"},Ms={class:"kpi-card bg-gray-800 border border-purple-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-purple-900/20"},Us={class:"kpi-value text-3xl font-bold text-purple-300"},Is={class:"kpi-section feedback-analysis"},Vs={class:"feedback-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},Bs={class:"feedback-cards grid grid-cols-1 sm:grid-cols-2 gap-6"},qs={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Ks={class:"kpi-value text-3xl font-bold text-white"},Ws={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},Qs={class:"kpi-value text-3xl font-bold text-white"},Hs={class:"feedback-sentiment bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Ys={class:"sentiment-display space-y-3"},Gs={class:"sentiment-item flex justify-between items-center"},Js={class:"sentiment-value font-bold text-green-400"},Xs={class:"sentiment-item flex justify-between items-center"},Zs={class:"sentiment-value font-bold text-yellow-400"},$s={class:"sentiment-item flex justify-between items-center"},ei={class:"sentiment-value font-bold text-red-400"},ti={class:"kpi-section session-analytics"},si={class:"kpi-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6"},ii={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},ai={class:"kpi-value text-3xl font-bold text-white"},oi={class:"kpi-card bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20"},li={class:"kpi-value text-3xl font-bold text-blue-300"},di={class:"kpi-card bg-gray-800 border border-green-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-green-900/20"},ri={class:"kpi-value text-3xl font-bold text-green-300"},ni={class:"kpi-card bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},ci={class:"kpi-value text-3xl font-bold text-white"},pi={class:"kpi-card bg-gray-800 border border-red-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-red-900/20"},vi={class:"kpi-value text-3xl font-bold text-red-300"},gi={class:"kpi-section performance-analysis"},mi={class:"performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},bi={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},xi={class:"table-container overflow-x-auto"},ui={class:"performance-table-content w-full"},hi={class:"divide-y divide-gray-600"},_i={class:"course-name px-4 py-3 text-sm text-white"},fi={class:"rating px-4 py-3 text-sm text-white"},ki={class:"completion px-4 py-3 text-sm text-white"},yi={key:0},wi={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Di={class:"table-container overflow-x-auto"},Ci={class:"performance-table-content w-full"},Pi={class:"divide-y divide-gray-600"},Si={class:"course-name px-4 py-3 text-sm text-white"},Ti={class:"rating low px-4 py-3 text-sm text-red-400"},zi={class:"issues px-4 py-3"},Ai={key:0},Ri={class:"kpi-section user-performance"},Ni={class:"performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},Ei={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Fi={class:"table-container overflow-x-auto"},Oi={class:"performance-table-content w-full"},Li={class:"divide-y divide-gray-600"},ji={class:"user-name px-4 py-3 text-sm text-white"},Mi={class:"score high px-4 py-3 text-sm text-green-400 font-semibold"},Ui={class:"courses px-4 py-3 text-sm text-white"},Ii={key:0},Vi={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Bi={class:"table-container overflow-x-auto"},qi={class:"performance-table-content w-full"},Ki={class:"divide-y divide-gray-600"},Wi={class:"user-name px-4 py-3 text-sm text-white"},Qi={class:"score low px-4 py-3 text-sm text-yellow-400 font-semibold"},Hi={class:"courses px-4 py-3 text-sm text-white"},Yi={key:0},Gi={class:"kpi-section online-top-performers"},Ji={class:"performance-grid grid grid-cols-1 lg:grid-cols-2 gap-8"},Xi={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},Zi={class:"table-container overflow-x-auto"},$i={class:"performance-table-content w-full"},ea={class:"divide-y divide-gray-600"},ta={class:"course-name px-4 py-3 text-sm text-white"},sa={class:"completion px-4 py-3 text-sm text-green-400 font-semibold"},ia={class:"enrolled px-4 py-3 text-sm text-white"},aa={key:0},oa={class:"performance-table bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow"},la={class:"table-container overflow-x-auto"},da={class:"performance-table-content w-full"},ra={class:"divide-y divide-gray-600"},na={class:"user-name px-4 py-3 text-sm text-white"},ca={class:"courses px-4 py-3 text-sm text-white"},pa={class:"progress px-4 py-3 text-sm text-green-400 font-semibold"},va={key:0},ga={class:"kpi-section engagement-trends"},ma={class:"trends-display"},ba={class:"trend-cards grid grid-cols-1 md:grid-cols-3 gap-6"},xa={class:"trend-card current bg-gray-800 border border-blue-700 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-blue-900/20"},ua={class:"trend-value text-3xl font-bold text-blue-300"},ha={class:"trend-label text-sm text-gray-300 mt-2"},_a={class:"trend-card previous bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},fa={class:"trend-value text-3xl font-bold text-gray-200"},ka={class:"trend-card comparison bg-gray-800 border border-gray-600 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow hover:bg-gray-750"},ya={class:"trend-arrow mr-2"},wa={class:"trend-percentage text-sm text-gray-300 mt-2"};function Da(o,t,i,l,_,Be){const Ue=$e("AdminLayout");return d(),et(Ue,{breadcrumbs:l.breadcrumbs},{default:tt(()=>{var Re,Ne,Ee,Fe,Oe,Le,k,je,n,c,g,v,m,b,y,w,D,C,P,S,T,z,A,R,N,E,F,O,L,j,M,U,I,V,B,q,K,W,Q,H,Y,G,J,X,Z,$,ee,te,se,ie,ae,oe,le,de,re,ne,ce,pe,ve,ge,me,be,xe,ue,he,_e,fe,ke,ye,we,De,Ce,Pe,Se,Te,ze,Ae,p,x,qe,Ke,We,Qe,He,Ye,Ge;return[e("div",ct,[e("div",pt,[e("div",vt,[e("div",gt,[t[10]||(t[10]=e("h1",{class:"dashboard-title text-2xl sm:text-3xl font-bold text-white"},"📊 Monthly Training KPI Report",-1)),e("p",mt,s(((Re=i.kpiData.period)==null?void 0:Re.period_name)||"Loading..."),1)]),e("div",bt,[e("button",{onClick:t[0]||(t[0]=a=>l.showFilters=!l.showFilters),class:Xe(["filter-btn bg-gray-700 text-gray-200 px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors",{"bg-blue-800 text-blue-200":l.showFilters}])}," 🔍 Filters ",2),e("div",xt,[e("button",{onClick:t[1]||(t[1]=(...a)=>l.generateDirectScreenshot&&l.generateDirectScreenshot(...a)),class:"export-btn screenshot-btn bg-purple-800 text-purple-200 px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50",disabled:l.loading||l.screenshotLoading},s(l.screenshotLoading?"⏳ Capturing...":"📸 Screenshot"),9,ut),e("button",{onClick:t[2]||(t[2]=(...a)=>l.exportCsv&&l.exportCsv(...a)),class:"export-btn csv-btn bg-green-800 text-green-200 px-4 py-2 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50",disabled:l.loading}," 📋 Export CSV ",8,ht)]),e("button",{onClick:t[3]||(t[3]=(...a)=>l.refreshData&&l.refreshData(...a)),class:"refresh-btn bg-indigo-800 text-indigo-200 px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50",disabled:l.loading},s(l.loading?"⏳":"🔄")+" Refresh ",9,_t)])]),Me(e("div",ft,[e("div",kt,[e("div",yt,[t[11]||(t[11]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Month",-1)),Me(e("select",{"onUpdate:modelValue":t[4]||(t[4]=a=>l.filters.month=a),onChange:t[5]||(t[5]=(...a)=>l.applyFilters&&l.applyFilters(...a)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[(d(!0),r(u,null,h(i.filterData.months,a=>(d(),r("option",{key:a.value,value:a.value},s(a.label),9,wt))),128))],544),[[Ie,l.filters.month]])]),e("div",Dt,[t[12]||(t[12]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Year",-1)),Me(e("select",{"onUpdate:modelValue":t[6]||(t[6]=a=>l.filters.year=a),onChange:t[7]||(t[7]=(...a)=>l.applyFilters&&l.applyFilters(...a)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[(d(!0),r(u,null,h(i.filterData.years,a=>(d(),r("option",{key:a.value,value:a.value},s(a.label),9,Ct))),128))],544),[[Ie,l.filters.year]])]),e("div",Pt,[t[14]||(t[14]=e("label",{class:"block text-sm font-medium text-gray-200 mb-2"},"Department",-1)),Me(e("select",{"onUpdate:modelValue":t[8]||(t[8]=a=>l.filters.department_id=a),onChange:t[9]||(t[9]=(...a)=>l.applyFilters&&l.applyFilters(...a)),class:"w-full bg-gray-600 border border-gray-500 text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400"},[t[13]||(t[13]=e("option",{value:""},"All Departments",-1)),(d(!0),r(u,null,h(i.filterData.departments,a=>(d(),r("option",{key:a.id,value:a.id},s(a.name),9,St))),128))],544),[[Ie,l.filters.department_id]])])])],512),[[st,l.showFilters]]),e("div",Tt,[e("span",zt,"Last updated: "+s(l.formatDateTime(i.lastUpdated)),1)])]),l.loading?(d(),r("div",At,t[15]||(t[15]=[e("div",{class:"loading-spinner bg-gray-800 rounded-lg p-8 text-center shadow-2xl border border-gray-600"},[e("div",{class:"spinner inline-block w-8 h-8 border-4 border-gray-600 border-t-blue-400 rounded-full animate-spin mb-4"}),e("p",{class:"text-white"},"Loading KPI Data...")],-1)]))):(d(),r("div",Rt,[e("section",Nt,[t[20]||(t[20]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📊 Training Delivery Overview",-1)),e("div",Et,[e("div",Ft,[t[16]||(t[16]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📚"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Courses Delivered")],-1)),e("div",Ot,s(((Ne=i.kpiData.delivery_overview)==null?void 0:Ne.courses_delivered)||0),1)]),e("div",Lt,[t[17]||(t[17]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"👥"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Enrolled")],-1)),e("div",jt,s(((Ee=i.kpiData.delivery_overview)==null?void 0:Ee.total_enrolled)||0),1)]),e("div",Mt,[t[18]||(t[18]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🎯"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Active Participants")],-1)),e("div",Ut,s(((Fe=i.kpiData.delivery_overview)==null?void 0:Fe.active_participants)||0),1)]),e("div",It,[t[19]||(t[19]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Completion Rate")],-1)),e("div",Vt,s(((Oe=i.kpiData.delivery_overview)==null?void 0:Oe.completion_rate)||0)+"%",1)])])]),e("section",Bt,[t[25]||(t[25]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🎯 Attendance & Engagement",-1)),e("div",qt,[e("div",Kt,[t[21]||(t[21]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📋"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Attendance Rate")],-1)),e("div",Wt,s(((Le=i.kpiData.attendance_engagement)==null?void 0:Le.average_attendance_rate)||0)+"%",1)]),e("div",Qt,[t[22]||(t[22]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⏱️"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Avg Time Spent")],-1)),e("div",Ht,s(((k=i.kpiData.attendance_engagement)==null?void 0:k.average_time_spent)||0)+"h",1)]),e("div",Yt,[t[23]||(t[23]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🕐"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Clock Consistency")],-1)),e("div",Gt,s(((je=i.kpiData.attendance_engagement)==null?void 0:je.clocking_consistency)||0)+"%",1)]),e("div",Jt,[t[24]||(t[24]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"💯"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Engagement Score")],-1)),e("div",Xt,s(((n=i.kpiData.attendance_engagement)==null?void 0:n.engagement_score)||0)+"%",1)])])]),e("section",Zt,[t[31]||(t[31]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"� Online Course Analytics Overview",-1)),e("div",$t,[e("div",es,[t[26]||(t[26]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"💻"),e("span",{class:"kpi-title text-sm font-medium text-blue-400"},"Online Courses")],-1)),e("div",ts,s(((g=(c=i.kpiData.online_course_analytics)==null?void 0:c.delivery)==null?void 0:g.online_courses_delivered)||0),1)]),e("div",ss,[t[27]||(t[27]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📝"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Enrollments")],-1)),e("div",is,s(((m=(v=i.kpiData.online_course_analytics)==null?void 0:v.delivery)==null?void 0:m.online_enrollments)||0),1)]),e("div",as,[t[28]||(t[28]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-green-400"},"Completed")],-1)),e("div",os,s(((y=(b=i.kpiData.online_course_analytics)==null?void 0:b.delivery)==null?void 0:y.online_completed)||0),1)]),e("div",ls,[t[29]||(t[29]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📊"),e("span",{class:"kpi-title text-sm font-medium text-purple-400"},"Completion Rate")],-1)),e("div",ds,s(((D=(w=i.kpiData.online_course_analytics)==null?void 0:w.delivery)==null?void 0:D.online_completion_rate)||0)+"%",1)]),e("div",rs,[t[30]||(t[30]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"👥"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Active Learners")],-1)),e("div",ns,s(((P=(C=i.kpiData.online_course_analytics)==null?void 0:C.delivery)==null?void 0:P.active_online_learners)||0),1)])])]),e("section",cs,[t[36]||(t[36]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🎥 Video Engagement Metrics",-1)),e("div",ps,[e("div",vs,[t[32]||(t[32]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"▶️"),e("span",{class:"kpi-title text-sm font-medium text-blue-400"},"Videos Watched")],-1)),e("div",gs,s(((T=(S=i.kpiData.online_course_analytics)==null?void 0:S.video_engagement)==null?void 0:T.total_videos_watched)||0),1)]),e("div",ms,[t[33]||(t[33]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-green-400"},"Avg Completion")],-1)),e("div",bs,s(((A=(z=i.kpiData.online_course_analytics)==null?void 0:z.video_engagement)==null?void 0:A.avg_video_completion)||0)+"%",1)]),e("div",xs,[t[34]||(t[34]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⏱️"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Watch Time")],-1)),e("div",us,s(((N=(R=i.kpiData.online_course_analytics)==null?void 0:R.video_engagement)==null?void 0:N.total_watch_time_hours)||0)+"h",1)]),e("div",hs,[t[35]||(t[35]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🔄"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Replays")],-1)),e("div",_s,s(((F=(E=i.kpiData.online_course_analytics)==null?void 0:E.video_engagement)==null?void 0:F.video_replay_count)||0),1)])])]),e("section",fs,[t[41]||(t[41]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📈 Learning Outcomes",-1)),e("div",ks,[e("div",ys,[t[37]||(t[37]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-green-400"},"Quiz Pass Rate")],-1)),e("div",ws,s(((O=i.kpiData.learning_outcomes)==null?void 0:O.quiz_pass_rate)||0)+"%",1)]),e("div",Ds,[t[38]||(t[38]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"❌"),e("span",{class:"kpi-title text-sm font-medium text-red-400"},"Quiz Fail Rate")],-1)),e("div",Cs,s(((L=i.kpiData.learning_outcomes)==null?void 0:L.quiz_fail_rate)||0)+"%",1)]),e("div",Ps,[t[39]||(t[39]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📊"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Average Score")],-1)),e("div",Ss,s(((j=i.kpiData.learning_outcomes)==null?void 0:j.average_quiz_score)||0)+"%",1)]),e("div",Ts,[t[40]||(t[40]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📈"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Improvement Rate")],-1)),e("div",zs,s(((M=i.kpiData.learning_outcomes)==null?void 0:M.improvement_rate)||0)+"%",1)])])]),e("section",As,[t[46]||(t[46]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📚 Online Module Progress",-1)),e("div",Rs,[e("div",Ns,[t[42]||(t[42]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📚"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Modules")],-1)),e("div",Es,s(((I=(U=i.kpiData.online_course_analytics)==null?void 0:U.module_progress)==null?void 0:I.total_modules)||0),1)]),e("div",Fs,[t[43]||(t[43]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"✅"),e("span",{class:"kpi-title text-sm font-medium text-green-400"},"Completed")],-1)),e("div",Os,s(((B=(V=i.kpiData.online_course_analytics)==null?void 0:V.module_progress)==null?void 0:B.completed_modules)||0),1)]),e("div",Ls,[t[44]||(t[44]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"👤"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Avg Per User")],-1)),e("div",js,s(((K=(q=i.kpiData.online_course_analytics)==null?void 0:q.module_progress)==null?void 0:K.avg_modules_per_user)||0),1)]),e("div",Ms,[t[45]||(t[45]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"📈"),e("span",{class:"kpi-title text-sm font-medium text-purple-400"},"Completion Rate")],-1)),e("div",Us,s(((Q=(W=i.kpiData.online_course_analytics)==null?void 0:W.module_progress)==null?void 0:Q.module_completion_rate)||0)+"%",1)])])]),e("section",Is,[t[53]||(t[53]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"⭐ Course Quality & Feedback",-1)),e("div",Vs,[e("div",Bs,[e("div",qs,[t[47]||(t[47]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⭐"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Average Rating")],-1)),e("div",Ks,s(((H=i.kpiData.feedback_analysis)==null?void 0:H.average_rating)||0)+"/5",1)]),e("div",Ws,[t[48]||(t[48]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"💬"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Feedback")],-1)),e("div",Qs,s(((Y=i.kpiData.feedback_analysis)==null?void 0:Y.total_feedback_count)||0),1)])]),e("div",Hs,[t[52]||(t[52]=e("h3",{class:"text-lg font-semibold text-white mb-4"},"Feedback Sentiment",-1)),e("div",Ys,[e("div",Gs,[t[49]||(t[49]=e("span",{class:"sentiment-label text-gray-300"},"😊 Positive:",-1)),e("span",Js,s(((J=(G=i.kpiData.feedback_analysis)==null?void 0:G.feedback_sentiment)==null?void 0:J.positive)||0)+"%",1)]),e("div",Xs,[t[50]||(t[50]=e("span",{class:"sentiment-label text-gray-300"},"😐 Neutral:",-1)),e("span",Zs,s(((Z=(X=i.kpiData.feedback_analysis)==null?void 0:X.feedback_sentiment)==null?void 0:Z.neutral)||0)+"%",1)]),e("div",$s,[t[51]||(t[51]=e("span",{class:"sentiment-label text-gray-300"},"😞 Negative:",-1)),e("span",ei,s(((ee=($=i.kpiData.feedback_analysis)==null?void 0:$.feedback_sentiment)==null?void 0:ee.negative)||0)+"%",1)])])])])]),e("section",ti,[t[59]||(t[59]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"⏱️ Learning Session Analytics",-1)),e("div",si,[e("div",ii,[t[54]||(t[54]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"🎯"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Total Sessions")],-1)),e("div",ai,s(((se=(te=i.kpiData.online_course_analytics)==null?void 0:te.session_analytics)==null?void 0:se.total_sessions)||0),1)]),e("div",oi,[t[55]||(t[55]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⏱️"),e("span",{class:"kpi-title text-sm font-medium text-blue-400"},"Avg Duration")],-1)),e("div",li,s(((ae=(ie=i.kpiData.online_course_analytics)==null?void 0:ie.session_analytics)==null?void 0:ae.avg_session_duration_minutes)||0)+"m",1)]),e("div",di,[t[56]||(t[56]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"👁️"),e("span",{class:"kpi-title text-sm font-medium text-green-400"},"Attention Score")],-1)),e("div",ri,s(((le=(oe=i.kpiData.online_course_analytics)==null?void 0:oe.session_analytics)==null?void 0:le.avg_attention_score)||0)+"%",1)]),e("div",ni,[t[57]||(t[57]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⏰"),e("span",{class:"kpi-title text-sm font-medium text-gray-300"},"Learning Hours")],-1)),e("div",ci,s(((re=(de=i.kpiData.online_course_analytics)==null?void 0:de.session_analytics)==null?void 0:re.total_learning_hours)||0)+"h",1)]),e("div",pi,[t[58]||(t[58]=e("div",{class:"kpi-header flex items-center mb-4"},[e("span",{class:"kpi-icon text-2xl mr-3"},"⚠️"),e("span",{class:"kpi-title text-sm font-medium text-red-400"},"Suspicious Activity")],-1)),e("div",vi,s(((ce=(ne=i.kpiData.online_course_analytics)==null?void 0:ne.session_analytics)==null?void 0:ce.suspicious_activity_count)||0),1)])])]),e("section",gi,[t[68]||(t[68]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🏆 Course Performance Analysis",-1)),e("div",mi,[e("div",bi,[t[62]||(t[62]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🥇 Top-Performing Courses",-1)),t[63]||(t[63]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on rating & completion",-1)),e("div",xi,[e("table",ui,[t[61]||(t[61]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Course Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Rating"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Completion %")])],-1)),e("tbody",hi,[(d(!0),r(u,null,h(((pe=i.kpiData.performance_analysis)==null?void 0:pe.top_performing_courses)||[],a=>(d(),r("tr",{key:a.id,class:"table-row hover:bg-gray-700 transition-colors"},[e("td",_i,s(a.name),1),e("td",fi,s(a.rating)+"/5",1),e("td",ki,s(a.completion_rate)+"%",1)]))),128)),(ge=(ve=i.kpiData.performance_analysis)==null?void 0:ve.top_performing_courses)!=null&&ge.length?f("",!0):(d(),r("tr",yi,t[60]||(t[60]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])]),e("div",wi,[t[66]||(t[66]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"⚠️ Courses Needing Improvement",-1)),t[67]||(t[67]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on dropout or low ratings",-1)),e("div",Di,[e("table",Ci,[t[65]||(t[65]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Course Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Rating"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Issues")])],-1)),e("tbody",Pi,[(d(!0),r(u,null,h(((me=i.kpiData.performance_analysis)==null?void 0:me.courses_needing_improvement)||[],a=>(d(),r("tr",{key:a.name,class:"table-row improvement-needed hover:bg-red-900/30 transition-colors"},[e("td",Si,s(a.name),1),e("td",Ti,s(a.rating||"N/A"),1),e("td",zi,[(d(!0),r(u,null,h(a.issues,Je=>(d(),r("span",{key:Je,class:"issue-tag inline-block bg-red-800 text-red-200 text-xs px-2 py-1 rounded mr-1 mb-1"},s(Je),1))),128))])]))),128)),(xe=(be=i.kpiData.performance_analysis)==null?void 0:be.courses_needing_improvement)!=null&&xe.length?f("",!0):(d(),r("tr",Ai,t[64]||(t[64]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No courses needing improvement",-1)])))])])])])])]),e("section",Ri,[t[77]||(t[77]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"👤 User Performance Analysis",-1)),e("div",Ni,[e("div",Ei,[t[71]||(t[71]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🌟 Top-Performing Users",-1)),t[72]||(t[72]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on evaluation system scores",-1)),e("div",Fi,[e("table",Oi,[t[70]||(t[70]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"User Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Score %"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Courses Completed")])],-1)),e("tbody",Li,[(d(!0),r(u,null,h(((ue=i.kpiData.performance_analysis)==null?void 0:ue.top_performing_users)||[],a=>(d(),r("tr",{key:a.name,class:"table-row top-performer hover:bg-green-900/30 transition-colors"},[e("td",ji,s(a.name),1),e("td",Mi,s(a.score)+"%",1),e("td",Ui,s(a.courses_completed||0),1)]))),128)),(_e=(he=i.kpiData.performance_analysis)==null?void 0:he.top_performing_users)!=null&&_e.length?f("",!0):(d(),r("tr",Ii,t[69]||(t[69]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])]),e("div",Vi,[t[75]||(t[75]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"📈 Users Needing Support",-1)),t[76]||(t[76]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on evaluation system scores",-1)),e("div",Bi,[e("table",qi,[t[74]||(t[74]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"User Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Score %"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Incomplete Courses")])],-1)),e("tbody",Ki,[(d(!0),r(u,null,h(((fe=i.kpiData.performance_analysis)==null?void 0:fe.low_performing_users)||[],a=>(d(),r("tr",{key:a.name,class:"table-row needs-support hover:bg-yellow-900/30 transition-colors"},[e("td",Wi,s(a.name),1),e("td",Qi,s(a.score)+"%",1),e("td",Hi,s(a.courses_incomplete||0),1)]))),128)),(ye=(ke=i.kpiData.performance_analysis)==null?void 0:ke.low_performing_users)!=null&&ye.length?f("",!0):(d(),r("tr",Yi,t[73]||(t[73]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No users needing support",-1)])))])])])])])]),e("section",Gi,[t[86]||(t[86]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"🏆 Online Course Top Performers",-1)),e("div",Ji,[e("div",Xi,[t[80]||(t[80]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🥇 Top Online Courses",-1)),t[81]||(t[81]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on completion rate & enrollment",-1)),e("div",Zi,[e("table",$i,[t[79]||(t[79]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Course Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Completion"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Enrolled")])],-1)),e("tbody",ea,[(d(!0),r(u,null,h(((De=(we=i.kpiData.online_course_analytics)==null?void 0:we.top_performers)==null?void 0:De.top_online_courses)||[],a=>(d(),r("tr",{key:a.id,class:"table-row hover:bg-gray-700 transition-colors"},[e("td",ta,s(a.name),1),e("td",sa,s(a.completion_rate)+"%",1),e("td",ia,s(a.total_enrolled),1)]))),128)),(Se=(Pe=(Ce=i.kpiData.online_course_analytics)==null?void 0:Ce.top_performers)==null?void 0:Pe.top_online_courses)!=null&&Se.length?f("",!0):(d(),r("tr",aa,t[78]||(t[78]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])]),e("div",oa,[t[84]||(t[84]=e("h3",{class:"text-lg font-semibold text-white mb-2"},"🌟 Top Online Learners",-1)),t[85]||(t[85]=e("p",{class:"subtitle text-sm text-gray-300 mb-4"},"Based on courses completed & progress",-1)),e("div",la,[e("table",da,[t[83]||(t[83]=e("thead",null,[e("tr",{class:"bg-gray-700 border-b border-gray-600"},[e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"User Name"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Completed"),e("th",{class:"px-4 py-3 text-left text-sm font-medium text-white"},"Progress")])],-1)),e("tbody",ra,[(d(!0),r(u,null,h(((ze=(Te=i.kpiData.online_course_analytics)==null?void 0:Te.top_performers)==null?void 0:ze.top_online_learners)||[],a=>(d(),r("tr",{key:a.id,class:"table-row top-performer hover:bg-green-900/30 transition-colors"},[e("td",na,s(a.name),1),e("td",ca,s(a.courses_completed||0),1),e("td",pa,s(a.avg_progress||0)+"%",1)]))),128)),(x=(p=(Ae=i.kpiData.online_course_analytics)==null?void 0:Ae.top_performers)==null?void 0:p.top_online_learners)!=null&&x.length?f("",!0):(d(),r("tr",va,t[82]||(t[82]=[e("td",{colspan:"3",class:"no-data px-4 py-8 text-center text-gray-400"},"No data available",-1)])))])])])])])]),e("section",ga,[t[91]||(t[91]=e("h2",{class:"section-title text-xl font-semibold text-white mb-6"},"📈 Monthly Engagement Trend",-1)),e("div",ma,[e("div",ba,[e("div",xa,[t[87]||(t[87]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"📊"),e("span",{class:"trend-title text-sm font-medium text-blue-400"},"Current Month Engagement")],-1)),e("div",ua,s(((qe=i.kpiData.engagement_trends)==null?void 0:qe.current_month_engagement)||0)+"%",1),e("div",ha,s(((Ke=i.kpiData.period)==null?void 0:Ke.period_name)||"Current Period"),1)]),e("div",_a,[t[88]||(t[88]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"📉"),e("span",{class:"trend-title text-sm font-medium text-gray-300"},"Previous Month Engagement")],-1)),e("div",fa,s(((We=i.kpiData.engagement_trends)==null?void 0:We.previous_month_engagement)||0)+"%",1),t[89]||(t[89]=e("div",{class:"trend-label text-sm text-gray-300 mt-2"},"Previous Period",-1))]),e("div",ka,[t[90]||(t[90]=e("div",{class:"trend-header flex items-center mb-4"},[e("span",{class:"trend-icon text-2xl mr-3"},"🔄"),e("span",{class:"trend-title text-sm font-medium text-gray-300"},"Trend Direction")],-1)),e("div",{class:Xe(["trend-value text-2xl font-bold flex items-center",l.getTrendClass((Qe=i.kpiData.engagement_trends)==null?void 0:Qe.trend_direction)])},[e("span",ya,s(l.getTrendArrow((He=i.kpiData.engagement_trends)==null?void 0:He.trend_direction)),1),it(" "+s(((Ye=i.kpiData.engagement_trends)==null?void 0:Ye.trend_direction)||"stable"),1)],2),e("div",wa,s(((Ge=i.kpiData.engagement_trends)==null?void 0:Ge.trend_percentage)||0)+"% change",1)])])])])]))])]}),_:1},8,["breadcrumbs"])}const Fa=rt(nt,[["render",Da],["__scopeId","data-v-ab574a12"]]);export{Fa as default};

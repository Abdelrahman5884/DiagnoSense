<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiagnoSense API | Clinical Support Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#030712] text-slate-300 flex items-center justify-center min-h-screen p-6 overflow-hidden">

    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-blue-900/10 rounded-full blur-[120px]"></div>
    </div>

    <div class="relative z-10 text-center p-10 border border-slate-800 rounded-3xl bg-slate-900/40 backdrop-blur-xl shadow-2xl max-w-xl w-full">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-6 tracking-tight">
            Welcome to <span class="text-blue-500">DiagnoSense</span> API
        </h1>

        <p class="text-slate-500 mb-10 text-lg leading-relaxed">
            The gateway to AI-powered clinical decision support.
            <span class="block mt-2 text-sm font-mono text-blue-400/70 uppercase tracking-widest text-xs">Version 2026.05</span>
        </p>

        <div class="space-y-6">
            <a href="/scalar" class="inline-flex items-center gap-3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-5 px-10 rounded-2xl transition-all shadow-lg shadow-blue-900/20 transform hover:scale-105 active:scale-95 group">
                <svg class="w-6 h-6 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Explore Scalar Docs
            </a>

            <div class="pt-6 border-t border-slate-800/50 flex justify-center items-center gap-4 text-[10px] text-slate-600 uppercase tracking-[0.2em]">
                <span class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                    API Node Active
                </span>
                <span>•</span>
                <span>Secure Environment</span>
            </div>
        </div>
    </div>
</body>
</html>

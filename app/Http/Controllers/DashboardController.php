<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with stats and agent data.
     */
    public function index()
    {
        $agents = $this->getAgents();

        $stats = [
            'total' => count($agents),
            'active' => count(array_filter($agents, fn ($a) => $a['status'] === 'active')),
            'idle' => count(array_filter($agents, fn ($a) => $a['status'] === 'idle')),
            'error' => count(array_filter($agents, fn ($a) => $a['status'] === 'error')),
            'tasks' => array_sum(array_column($agents, 'tasksCompleted')),
            'uptime' => count($agents) > 0
                ? round(array_sum(array_map(fn ($a) => (float) ($a['uptime'] ?? 0), $agents)) / count($agents), 1)
                : 0,
        ];

        return view('pages.dashboard', compact('agents', 'stats'));
    }

    /**
     * Get agents from session or return defaults.
     * In production, this would come from the database.
     */
    private function getAgents(): array
    {
        // Try session first (for demo CRUD persistence)
        if (session()->has('agents')) {
            return session('agents');
        }

        // Default seed data
        return [
            ['id' => '1', 'name' => 'Customer Support AI', 'type' => 'Conversational', 'status' => 'active',  'model' => 'Claude Opus 4',   'tasksCompleted' => 1247, 'uptime' => '99.9%', 'lastActive' => '2 min ago',  'color' => '#E2725B'],
            ['id' => '2', 'name' => 'Code Review Bot',      'type' => 'Automation',    'status' => 'active',  'model' => 'Claude Sonnet 4', 'tasksCompleted' => 893,  'uptime' => '99.7%', 'lastActive' => '5 min ago',  'color' => '#7C3AED'],
            ['id' => '3', 'name' => 'Data Analyzer Pro',    'type' => 'Analytics',     'status' => 'idle',    'model' => 'Claude Opus 4',   'tasksCompleted' => 562,  'uptime' => '99.5%', 'lastActive' => '1 hour ago', 'color' => '#3B82C4'],
            ['id' => '4', 'name' => 'Content Writer',       'type' => 'Generative',    'status' => 'active',  'model' => 'Claude Sonnet 4', 'tasksCompleted' => 2104, 'uptime' => '99.8%', 'lastActive' => 'Just now',   'color' => '#2D8B5E'],
            ['id' => '5', 'name' => 'Security Auditor',     'type' => 'Automation',    'status' => 'error',   'model' => 'Claude Opus 4',   'tasksCompleted' => 78,   'uptime' => '95.2%', 'lastActive' => '3 hours ago', 'color' => '#D14343'],
            ['id' => '6', 'name' => 'Translation Engine',   'type' => 'Generative',    'status' => 'active',  'model' => 'Claude Haiku 4',  'tasksCompleted' => 3451, 'uptime' => '99.9%', 'lastActive' => '10 min ago', 'color' => '#C7821A'],
            ['id' => '7', 'name' => 'Research Assistant',   'type' => 'Analytics',     'status' => 'idle',    'model' => 'Claude Opus 4',   'tasksCompleted' => 431,  'uptime' => '98.9%', 'lastActive' => '30 min ago', 'color' => '#4E2C23'],
            ['id' => '8', 'name' => 'Email Classifier',     'type' => 'Automation',    'status' => 'active',  'model' => 'Claude Haiku 4',  'tasksCompleted' => 9876, 'uptime' => '100%',  'lastActive' => '1 min ago',  'color' => '#7C3AED'],
        ];
    }
}

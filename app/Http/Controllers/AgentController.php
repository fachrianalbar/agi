<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * Display a listing of the agents.
     */
    public function index()
    {
        $agents = session('agents', $this->defaultAgents());
        $stats = [
            'total'  => count($agents),
            'active' => count(array_filter($agents, fn($a) => $a['status'] === 'active')),
            'idle'   => count(array_filter($agents, fn($a) => $a['status'] === 'idle')),
            'error'  => count(array_filter($agents, fn($a) => $a['status'] === 'error')),
        ];

        return view('pages.agents', compact('agents', 'stats'));
    }

    /**
     * Store a newly created agent in session.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:Conversational,Automation,Analytics,Generative',
            'model'       => 'required|string',
            'status'      => 'required|string|in:active,idle',
            'description' => 'nullable|string|max:1000',
        ]);

        $agents = session('agents', $this->defaultAgents());

        $newAgent = [
            'id'             => (string) (count($agents) + 1),
            'name'           => $validated['name'],
            'type'           => $validated['type'],
            'status'         => $validated['status'],
            'model'          => $validated['model'],
            'description'    => $validated['description'] ?? '',
            'tasksCompleted' => 0,
            'uptime'         => '—',
            'lastActive'     => 'Just now',
            'color'          => '#' . substr(md5($validated['name']), 0, 6),
        ];

        array_unshift($agents, $newAgent);
        session(['agents' => $agents]);

        return redirect()->route('agents.index')->with('success', "Agent \"{$validated['name']}\" created successfully.");
    }

    /**
     * Update the specified agent in session.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:Conversational,Automation,Analytics,Generative',
            'model'       => 'required|string',
            'status'      => 'required|string|in:active,idle',
            'description' => 'nullable|string|max:1000',
        ]);

        $agents = session('agents', $this->defaultAgents());

        foreach ($agents as &$agent) {
            if ($agent['id'] === $id) {
                $agent = array_merge($agent, $validated);
                break;
            }
        }

        session(['agents' => $agents]);

        return redirect()->route('agents.index')->with('success', "Agent \"{$validated['name']}\" updated successfully.");
    }

    /**
     * Remove the specified agent from session.
     */
    public function destroy(string $id)
    {
        $agents = session('agents', $this->defaultAgents());
        $agent = collect($agents)->firstWhere('id', $id);

        $agents = array_values(array_filter($agents, fn($a) => $a['id'] !== $id));
        session(['agents' => $agents]);

        $name = $agent['name'] ?? 'Unknown';
        return redirect()->route('agents.index')->with('info', "Agent \"{$name}\" deleted.");
    }

    /**
     * Default seed agents.
     */
    private function defaultAgents(): array
    {
        return [
            ['id' => '1', 'name' => 'Customer Support AI', 'type' => 'Conversational', 'status' => 'active',  'model' => 'Claude Opus 4',   'tasksCompleted' => 1247, 'uptime' => '99.9%', 'lastActive' => '2 min ago',  'color' => '#E2725B'],
            ['id' => '2', 'name' => 'Code Review Bot',      'type' => 'Automation',    'status' => 'active',  'model' => 'Claude Sonnet 4', 'tasksCompleted' => 893,  'uptime' => '99.7%', 'lastActive' => '5 min ago',  'color' => '#7C3AED'],
            ['id' => '3', 'name' => 'Data Analyzer Pro',    'type' => 'Analytics',     'status' => 'idle',    'model' => 'Claude Opus 4',   'tasksCompleted' => 562,  'uptime' => '99.5%', 'lastActive' => '1 hour ago', 'color' => '#3B82C4'],
            ['id' => '4', 'name' => 'Content Writer',       'type' => 'Generative',    'status' => 'active',  'model' => 'Claude Sonnet 4', 'tasksCompleted' => 2104, 'uptime' => '99.8%', 'lastActive' => 'Just now',   'color' => '#2D8B5E'],
            ['id' => '5', 'name' => 'Security Auditor',     'type' => 'Automation',    'status' => 'error',   'model' => 'Claude Opus 4',   'tasksCompleted' => 78,   'uptime' => '95.2%', 'lastActive' => '3 hours ago','color' => '#D14343'],
            ['id' => '6', 'name' => 'Translation Engine',   'type' => 'Generative',    'status' => 'active',  'model' => 'Claude Haiku 4',  'tasksCompleted' => 3451, 'uptime' => '99.9%', 'lastActive' => '10 min ago', 'color' => '#C7821A'],
            ['id' => '7', 'name' => 'Research Assistant',   'type' => 'Analytics',     'status' => 'idle',    'model' => 'Claude Opus 4',   'tasksCompleted' => 431,  'uptime' => '98.9%', 'lastActive' => '30 min ago', 'color' => '#4E2C23'],
            ['id' => '8', 'name' => 'Email Classifier',     'type' => 'Automation',    'status' => 'active',  'model' => 'Claude Haiku 4',  'tasksCompleted' => 9876, 'uptime' => '100%',  'lastActive' => '1 min ago',  'color' => '#7C3AED'],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Models\Event;

class EventController extends Controller
{
    
    public function index() {

        $search = request('search');

        if($search) {

            $events = Event::where([
                ['title', 'like', '%'.$search.'%']
            ])->get();

        } else {
            $events = Event::all();
        }        
    
        return view('welcome',['events' => $events, 'search' => $search]);

    }

    public function create() {
        return view('events.create');
    }

    public function store(Request $request) {

        $event = new Event;

        $event->title = $request->title;
        $event->date  = $request->date;
        $event->city  = $request->city;
        $event->private = $request->private;
        $event->description = $request->description;
        $event->items = $request->items;

        $user = auth()->user();
        $event->user_id = $user->id;

        // Image Upload
        if($request->hasFile('image') && $request->file('image')->isValid()) {

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $event->image = $imageName;

        }
        
        $event->save();

        return redirect('/')->with('msg', 'Evento criado com sucesso!');

    }

    public function show($id) {


        $event = Event::findOrFail($id);

        $user = auth()->user();
        $hasUserJoined = false;
        
        if($user) {
                $userEvents = $user->eventAsParticipant->toArray();
                foreach($userEvents as $userEvent){
                    if($userEvent['id'] == $id){
                        $hasUserJoined = true;
                    }
                }
        }


        $eventOwner = User::where('id',$event->user_id)->first()->toArray();

        return view('events.show', ['event' => $event , 'eventOwner' => $eventOwner, 'hasuserjoined' => $hasUserJoined]);
        
    }


    public function dashboard(){
        
        $user = auth()->user();

        $events = $user->events;

        $eventsAsParticipant = $user->eventAsParticipant;

        return view('events.dashboard',[
            'events'=>$events,
         'eventsasparticipant' => $eventsAsParticipant
        ]);

    }

    public function destroy($id) {

        Event::findOrFail($id)->delete();

        return redirect('/dashboard')->with('msg','Evento excluido com sucesso!');
    }

    public function edit($id){

        $event = Event::findOrFail($id);

        return view('events.edit',['event'=> $event ]);

    }

    public function update(Request $request){


        $data = $request->all();
        
        // Image Upload
        if($request->hasFile('image') && $request->file('image')->isValid()) {

            $requestImage = $request->image;

            $extension = $requestImage->extension();

            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;

            $requestImage->move(public_path('img/events'), $imageName);

            $data['image'] = $imageName;

        }

        Event::findOrFail($request->id)->update($data);

        return redirect('/dashboard')->with('msg','Evento alterado com sucesso!');

    }

    public function join($id){

        $user = auth()->user();

        $user->eventAsParticipant()->attach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg','Presença confirmada no evento '. $event->title .'.');

    }

    public function leaveEvent($id){

        $user = auth()->user();

        $user->eventAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg','Presença cancelada com sucesso '. $event->title .'.');

    }
}
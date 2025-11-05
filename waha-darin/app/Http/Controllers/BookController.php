<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::select('id', 'title', 'slug', 'description', 'image', 'author_id', 'publisher_id', 'internal_code')
            ->with('author:id,name,slug,avatar', 'publisher:name,id,slug,avatar')
            ->inRandomOrder()
            ->Filter($request)
            ->paginate(20);
        return response()->json($books, 200);
    }


    public function show(Book $book)
    {
        $book = $book->load('author', 'categories', 'publisher');
        return response()->json($book, 200);
    }

    public function importBooks(Request  $request){
        if (!auth()->user()->hasRole("admin")) return response("Unauthorized to do this action",401);
        $request->validate([
            "file" => "required|mimes:csv,txt|max:5120"
        ]);
        $books = $this->prepareBooks($request->file("file")->getRealPath());
        foreach ($books as $book) {
            $this->createBook($book);
        }
    }

    private function prepareBooks($filePath){
        $file = file($filePath);
        $books =[];
        foreach ($file as $line) {
            $books[] = str_getcsv($line);
        }
        $cols = $books[0];
        unset($books[0]);
        $mapper =[];
        foreach ($books as  $book){
            $bookMapper =[];
            foreach ($book as $index => $value){
                $bookMapper[$cols[$index]]=$value;
            }
            $mapper[] = $bookMapper;
        }
        return $mapper;
    }


        private function createBook($bookObject)
        {
            $book = Book::where('title', $bookObject['Title'])->first();
            if ($book || !$bookObject['Title']) return;
            $book = new Book();
            $book->title = $bookObject['Title'];
            $book->price = (int)$bookObject['Price'] ? (int)$bookObject['Price'] : 0;
            $book->slug = str_slug($bookObject['Title']);

            $book->ISBN = $bookObject['ISBN'] ? $bookObject['ISBN'] : 0;
            $book->Available = true;
            $book->year = (int)$bookObject['Year'] ? (int)$bookObject['Year'] : 0000;
            $book->description = '-';
            $book->author_id = $this->findOrCreateAuthor($bookObject['Author']);
            $book->publisher_id = $this->findOrCreatePublisher($bookObject['Publisher']);
//            if ($bookObject['imageURL']) $book->image = $this->getOurImageUrl($bookObject['imageURL']);
            $categories = $this->getBookCategories([$bookObject['Category']]);
            $book->save();
            $book->categories()->sync($categories);
        }

//        public function updateBook(Request  $request)
//        {
//            $book = Book::where('id', $request['id'])->first();
//            if (!$book) return;
//
//            $book->title = $request['title'];
//            $book->price = (int)$request['price'] ? (int)$request['price'] : 0;
//            $book->slug = str_slug($request['title']);
//
//            $book->ISBN = $request['isbn'] ? $request['isbn'] : $book->ISBN;
//            $book->Available = true;
//            $book->year = (int)$request['year'] ? (int)$request['year'] : 0000;
//            $book->description = $request['description'] ? $request['description'] : '-';
//            $book->author_id = $request['author'] ? $this->findOrCreateAuthor($request['author']) : $book->author_id;
//            $book->publisher_id = $request['publisher'] ? $this->findOrCreatePublisher($request['publisher']) : $book->publisher_id;
//            $book->save();
//        }

        private function findOrCreateAuthor($name)
        {
            // Find author & return it's id
            $author = Author::where('name', $name)->first();
            if ($author) return $author->id;

            // Create one if isn't already there
            $author = new Author();
            $author->name = $name;
            $author->slug = str_slug($name, '-');
            $author->save();
            return $author->id;
        }

        private function findOrCreatePublisher($name)
        {
            // Find publisher & return it's id
            $publisher = Publisher::where('name', $name)->first();
            if ($publisher) return $publisher->id;

            // Create one if isn't already there
            $publisher = new Publisher();
            $publisher->name = $name;
            $publisher->slug = str_slug($name, '-');
            $publisher->save();
            return $publisher->id;
        }

        private function findOrCreateCategory($name)
        {
            // Find category & return it's id
            $category = Category::where('name', $name)->first();
            if ($category) return $category->id;

            // Create one if isn't already there
            $category = new Category();
            $category->name = $name;
            $category->slug = str_slug($name, '-');
            $category->save();
            return $category->id;
        }

        private function getBookCategories($bookCategories)
        {
            $categories = [];
            foreach ($bookCategories as $category) {
                array_push($categories, $this->findOrCreateCategory($category));
            }
            return $categories;
        }

        private function getOurImageUrl($imgUrl)
        {
            $client = new \GuzzleHttp\Client();
            $randomString = str_random(22);
            $booksPath = 'books/April2020/' . $randomString . '.jpg';
            $client->request('GET', $imgUrl, [
                'sink' => storage_path('app/public/' . $booksPath)
            ]);
            return $booksPath;
        }
}

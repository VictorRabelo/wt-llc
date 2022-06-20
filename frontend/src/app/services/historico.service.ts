import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { map } from "rxjs/operators";
import { environment } from "src/environments/environment";

@Injectable({
  providedIn: 'root'
})
export class HistoricoService {

  base_url = `${environment.apiUrl}/historicos`;

  constructor(
    private http: HttpClient
  ) { }

  getAll(queryParams: any = {}) {
    return this.http.get<any>(`${this.base_url}`, {params: queryParams}).pipe(map(res =>{ return res.response }));
  }

  getById(id: number) {
    return this.http.get<any>(`${this.base_url}/${id}`).pipe(map(res =>{ return res.response }));
  }

  store(store: any){
      return this.http.post<any>(`${this.base_url}`, store);
  }

  update(id: number, update: any){
    return this.http.put<any>(`${this.base_url}/${id}`, update);
  }

  delete(id: number){
    return this.http.delete<any>(`${this.base_url}/${id}`);
  }
}

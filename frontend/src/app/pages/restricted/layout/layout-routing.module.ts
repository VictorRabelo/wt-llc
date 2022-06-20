import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { CategoriasComponent } from './categorias/categorias.component';
import { ClientesComponent } from './clientes/clientes.component';
import { UsersComponent } from './users/users.component';
import { EntregasComponent } from './entregas/entregas.component';
import { EstoqueComponent } from './estoque/estoque.component';
import { FornecedoresComponent } from './fornecedores/fornecedores.component';
import { HomeComponent } from './home/home.component';
import { RelatoriosComponent } from './relatorios/relatorios.component';
import { SalesComponent } from './sales/sales.component';
import { SaleDetalheComponent } from './sales/sale-detalhe/sale-detalhe.component';
import { MovitionComponent } from './movition/movition.component';
import { EntregaDetalheComponent } from './entregas/entrega-detalhe/entrega-detalhe.component';
import { EntregasDespesasComponent } from './entregas/entregas-despesas/entregas-despesas.component';
import { ContabilidadesComponent } from './contabilidades/contabilidades.component';
import { ContabilidadeDetalheComponent } from './contabilidades/contabilidade-detalhe/contabilidade-detalhe.component';

const routes: Routes = [
  
  {path: '', pathMatch: 'full', redirectTo: 'home'},
      
  {path: 'home', component: HomeComponent, data: { animation: 'HomePage' }},

  {
    path: 'vendas', children: [
      { path: '', component: SalesComponent},
      { path: ':id', component: SaleDetalheComponent},
    ], data: { animation: 'VendasPage' }
  },

  {
    path: 'entregas', children: [
      { path: '', component: EntregasComponent},
      { path: 'despesas', component: EntregasDespesasComponent},
      { path: ':id', component: EntregaDetalheComponent},
    ], data: { animation: 'EntregasPage' }
  },
      
  {path: 'estoque', component: EstoqueComponent, data: { animation: 'EstoquePage' }},
      
  {path: 'clientes', component: ClientesComponent, data: { animation: 'ClientesPage' }},

  {path: 'usuarios', component: UsersComponent, data: { animation: 'UsuariosPage' }},

  {path: 'fornecedores', component: FornecedoresComponent, data: { animation: 'FornecedoresPage' }},

  {path: 'relatorios', component: RelatoriosComponent, data: { animation: 'RelatoriosPage' }},
      
  {path: 'categorias', component: CategoriasComponent, data: { animation: 'CategoriasPage' }},
  
  {
    path: 'contabilidades', children: [
      { path: '', component: ContabilidadesComponent},
      { path: ':id', component: ContabilidadeDetalheComponent},
    ], data: { animation: 'ContabilidadesPage' }
  },

  {path: 'contabilidades', component: ContabilidadesComponent, data: { animation: 'ContabilidadesPage' }},

  {
    path: 'movimentacao', children: [
      { path: 'diaria', component: MovitionComponent },
      { path: 'geral', component: MovitionComponent },
      { path: 'eletronico', component: MovitionComponent },
      { path: 'historico', component: MovitionComponent },
    ],
    data: { animation: 'MovimentacaoPage' }
  },

  {path: 'caixa', loadChildren: () => import('./caixa/caixa.module').then(m => m.CaixaModule), data: { animation: 'CaixaPage' }},
  
];
  
@NgModule({
    imports: [
      RouterModule.forChild(routes)
    ],
    exports: [
      RouterModule
    ]
})
export class LayoutRoutingModule {}